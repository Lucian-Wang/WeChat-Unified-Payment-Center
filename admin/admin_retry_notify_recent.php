<?php
require_once __DIR__ . '/../db.php';

// === 配置项 ===
$minutes_window = 10; // 只重试10分钟内的订单，可自行调整
$limit = 20; // 每次最多重试多少条，防止过载

$db = get_db();

$time_limit = date('Y-m-d H:i:s', time() - $minutes_window * 60);

$stmt = $db->prepare(
    "SELECT * FROM pay_orders 
     WHERE status='paid' 
     AND biz_notified=0 
     AND paid_at >= ? 
     LIMIT ?"
);
$stmt->bind_param('si', $time_limit, $limit);
$stmt->execute();
$res = $stmt->get_result();

while ($order = $res->fetch_assoc()) {
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

    // 记录本次结果
    $biz_notified = (stripos($response, 'OK') !== false) ? 1 : 0;
    $biz_notify_result = mb_substr($response, 0, 255);

    $upd = $db->prepare("UPDATE pay_orders SET biz_notified=?, biz_notify_result=? WHERE pay_order_no=?");
    $upd->bind_param('iss', $biz_notified, $biz_notify_result, $order['pay_order_no']);
    $upd->execute();
    $upd->close();

    echo "[{$order['pay_order_no']}] 通知返回: $biz_notify_result\n";
}

echo "done\n";