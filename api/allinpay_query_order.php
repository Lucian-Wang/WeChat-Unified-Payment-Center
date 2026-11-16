<?php
require_once '../db.php';
require_once '../allinpay/allinpay.php'; // 需有allinpay_query_order函数

$pay_order_no = $_GET['pay_order_no'] ?? $_POST['pay_order_no'] ?? '';
if (!$pay_order_no) {
    echo json_encode(['code'=>1,'msg'=>'缺少pay_order_no']);
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || !isset($order['status'])) {
    echo json_encode(['code'=>2,'msg'=>'订单不存在或状态异常']);
    exit;
}

// 如果本地已标记为已支付，直接返回
if ($order['status'] === 'paid') {
    echo json_encode([
        'code'=>0,
        'status'=>'paid',
        'paid_at'=>$order['paid_at']??'',
        'msg'=>'ok'
    ]);
    exit;
}

// 主动查通联
$result = allinpay_query_order($pay_order_no);

// 只要trxstatus为0000就认为已支付（通联返回没有status字段时也适用）
if (
    $result
    && isset($result['retcode']) && $result['retcode'] === 'SUCCESS'
    && isset($result['trxstatus']) && $result['trxstatus'] === '0000'
) {
    // 标记订单为已支付
    $now = date('Y-m-d H:i:s');
    $stmt2 = $db->prepare("UPDATE pay_orders SET status='paid', paid_at=?, updated_at=? WHERE pay_order_no=?");
    $stmt2->bind_param('sss', $now, $now, $pay_order_no);
    $stmt2->execute();
    $stmt2->close();

    // 业务系统通知
    if (!empty($order['notify_url'])) {
        $notify = [
            'biz_order_no' => $order['biz_order_no'],
            'pay_order_no' => $order['pay_order_no'],
            'user_id'      => $order['user_id'],
            'amount'       => number_format($order['amount'], 2, '.', ''),
            'status'       => 'paid',
            'paid_at'      => $now,
        ];
        $secret = getenv('PAYMENT_NOTIFY_SECRET') ?: '';
    if (strlen($secret) < 32) { throw new RuntimeException('Configure PAYMENT_NOTIFY_SECRET (at least 32 characters) on both systems.'); } // 你自己的签名密钥
        $notify['sign'] = md5($notify['biz_order_no'].$notify['pay_order_no'].$notify['user_id'].$notify['amount'].$secret);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $order['notify_url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($notify));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $response = curl_exec($ch);
        curl_close($ch);

        $biz_notified = (stripos($response, 'OK') !== false) ? 1 : 0;
        $biz_notify_result = mb_substr($response, 0, 255);
        $stmt3 = $db->prepare("UPDATE pay_orders SET biz_notified=?, biz_notify_result=? WHERE pay_order_no=?");
        $stmt3->bind_param('iss', $biz_notified, $biz_notify_result, $pay_order_no);
        $stmt3->execute();
        $stmt3->close();
    }

    echo json_encode([
        'code'=>0,
        'status'=>'paid',
        'paid_at'=>$now,
        'msg'=>'ok'
    ]);
    exit;
}

// 未支付
echo json_encode(['code'=>0,'status'=>'unpaid','msg'=>'未支付']);
exit;