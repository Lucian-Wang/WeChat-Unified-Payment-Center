<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../allinpay/allinpay.php';

$data = $_POST;
if (!isset($data['sign'])) {
    echo 'fail'; exit;
}
$sign = $data['sign'];
unset($data['sign']);
ksort($data);
$verifyStr = '';
foreach ($data as $k => $v) {
    if ($v !== '' && $v !== null && $k != 'sign') {
        $verifyStr .= $k . '=' . $v . '&';
    }
}
$verifyStr = rtrim($verifyStr, '&');
$allinpay_cfg = $GLOBALS['allinpay_cfg'];
$allinpayPub = file_get_contents($allinpay_cfg['pubKeyPath']);
$publicKey = openssl_get_publickey($allinpayPub);
$verifyResult = openssl_verify($verifyStr, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA1);
unset($publicKey);

if ($verifyResult !== 1) {
    echo 'fail'; exit;
}

$pay_order_no = $data['out_trade_no'] ?? $data['orderNo'] ?? '';
if (!$pay_order_no) {
    echo 'fail'; exit;
}
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo 'fail'; exit;
}
if ($order['status'] === 'paid') {
    echo 'success'; exit;
}

// 可选：校验金额
if (isset($data['amount'])) {
    $order_amount_fen = intval(round($order['amount'] * 100));
    if ($order_amount_fen != intval($data['amount'])) {
        echo 'fail'; exit;
    }
}

// 更新订单
$now = date('Y-m-d H:i:s');
$raw = json_encode($_POST, JSON_UNESCAPED_UNICODE);
$stmt2 = $db->prepare("UPDATE pay_orders SET status='paid', paid_at=?, allinpay_notify_raw=?, updated_at=?, allinpay_notified=1 WHERE pay_order_no=?");
$stmt2->bind_param('ssss', $now, $raw, $now, $pay_order_no);
$stmt2->execute();
$stmt2->close();

// 通知业务系统（同微信）
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
    if (strlen($secret) < 32) { throw new RuntimeException('Configure PAYMENT_NOTIFY_SECRET (at least 32 characters) on both systems.'); }
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

echo 'success';
exit;