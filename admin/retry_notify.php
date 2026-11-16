<?php
require_once 'admin_auth.php'; // 后台登录校验
require_once '../db.php';

function h($s) { return htmlspecialchars($s); }

$err = "";
$info = "";
$order = null;

if (empty($_GET['pay_order_no'])) {
    $err = "缺少 pay_order_no 参数";
} else {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=? LIMIT 1");
    $stmt->bind_param('s', $_GET['pay_order_no']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $err = "订单不存在";
    } elseif ($order['status'] !== 'paid') {
        $err = "订单未支付，无法通知";
    } elseif (empty($order['notify_url'])) {
        $err = "没有设置业务回调URL";
    }
}

if (!$err && $order) {
    // 构造通知参数
    $notify = [
        'biz_order_no' => $order['biz_order_no'],
        'pay_order_no' => $order['pay_order_no'],
        'user_id'      => $order['user_id'],
        'amount'       => number_format($order['amount'], 2, '.', ''),
        'status'       => 'paid',
        'paid_at'      => $order['paid_at'],
    ];
    $secret = getenv('PAYMENT_NOTIFY_SECRET') ?: '';
    if (strlen($secret) < 32) { throw new RuntimeException('Configure PAYMENT_NOTIFY_SECRET (at least 32 characters) on both systems.'); } // 和业务系统一致
    $notify['sign'] = md5($notify['biz_order_no'] . $notify['pay_order_no'] . $notify['user_id'] . $notify['amount'] . $secret);

    // 发送通知
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $order['notify_url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($notify));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $response = curl_exec($ch);
    curl_close($ch);

    // 结果写回数据库
    $biz_notified = (stripos($response, 'OK') !== false) ? 1 : 0;
    $biz_notify_result = mb_substr($response, 0, 255);
    $upd = $db->prepare("UPDATE pay_orders SET biz_notified=?, biz_notify_result=? WHERE pay_order_no=?");
    $upd->bind_param('iss', $biz_notified, $biz_notify_result, $order['pay_order_no']);
    $upd->execute();
    $upd->close();

    if ($biz_notified) {
        $info = "业务系统通知成功！";
    } else {
        $err = "通知失败：" . h($biz_notify_result);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>重新通知业务系统 - 支付中心后台</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {background:#f8f9fb;font-family: 'Segoe UI','Microsoft YaHei',Arial,sans-serif;}
        .main {max-width:540px;margin:40px auto;background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,0.06);padding:32px 28px;}
        h2 {margin-bottom:20px;}
        .result {font-size: 18px; margin-bottom: 18px;}
        .msg-ok {color: #27ae60; font-weight: bold;}
        .msg-err {color: #e74c3c; font-weight:bold;}
        .order-info {background:#f7fafd;border-radius:6px;padding:15px 18px;margin-bottom:18px;}
        .order-info b {color:#1687e0;}
        a.btn {
            display:inline-block;
            background: #409eff;
            color:#fff;
            padding: 8px 26px;
            border-radius: 4px;
            text-decoration:none;
            font-size: 15px;
            margin-top: 14px;
            transition: background 0.2s;
        }
        a.btn:hover {background:#237ad3;}
        .field {margin:3px 0;}
        .lab {color:#888;}
    </style>
</head>
<body>
<div class="main">
    <h2>重试业务系统通知</h2>
    <?php if($err): ?>
        <div class="result msg-err"><?= $err ?></div>
    <?php elseif($info): ?>
        <div class="result msg-ok"><?= $info ?></div>
    <?php endif; ?>

    <?php if($order): ?>
    <div class="order-info">
        <div class="field"><span class="lab">支付订单号：</span><b><?=h($order['pay_order_no'])?></b></div>
        <div class="field"><span class="lab">业务订单号：</span><?=h($order['biz_order_no'])?></div>
        <div class="field"><span class="lab">用户ID：</span><?=h($order['user_id'])?></div>
        <div class="field"><span class="lab">金额：</span><?=h($order['amount'])?></div>
        <div class="field"><span class="lab">状态：</span><?=h($order['status'])?></div>
        <div class="field"><span class="lab">支付时间：</span><?=h($order['paid_at'])?></div>
        <div class="field"><span class="lab">回调URL：</span><?=h($order['notify_url'])?></div>
        <div class="field"><span class="lab">本次回调结果：</span><?= isset($biz_notify_result) ? h($biz_notify_result) : '' ?></div>
    </div>
    <?php endif; ?>
    <a class="btn" href="index.php">返回订单列表</a>
</div>
</body>
</html>