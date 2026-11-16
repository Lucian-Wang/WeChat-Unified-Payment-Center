<?php
require_once '../db.php';

$config = require '../config.php';
$api_key = $config['wechat']['api_key'];

// 1. 读取微信回调XML
$xmlData = file_get_contents("php://input");
$data = (array)simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);

// 2. 签名校验
if (!isset($data['sign'])) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数错误]]></return_msg></xml>";
    exit;
}
$sign = $data['sign'];
unset($data['sign']);
ksort($data);
$stringA = '';
foreach ($data as $k => $v) $stringA .= "$k=$v&";
$stringSignTemp = $stringA . "key=" . $api_key;
$sign_check = strtoupper(md5($stringSignTemp));
if ($sign !== $sign_check) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>";
    exit;
}

// 3. 订单号
$pay_order_no = $data['out_trade_no'] ?? '';
if (!$pay_order_no) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[无订单号]]></return_msg></xml>";
    exit;
}

// 4. 数据库查订单
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单不存在]]></return_msg></xml>";
    exit;
}
if ($order['status'] === 'paid') {
    echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
    exit;
}

// 5. 金额校验
$order_amount_fen = intval(round($order['amount'] * 100));
if ($order_amount_fen != intval($data['total_fee'])) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[金额异常]]></return_msg></xml>";
    exit;
}

// 6. 更新订单
$now = date('Y-m-d H:i:s');
$raw = $xmlData;
$update = $db->prepare("UPDATE pay_orders SET status='paid', paid_at=?, wx_notify_raw=?, updated_at=?, wx_notified=1 WHERE pay_order_no=?");
$update->bind_param('ssss', $now, $raw, $now, $pay_order_no);
$update->execute();
$update->close();

// 7. 通知业务系统
if (!empty($order['notify_url'])) {
    $notify = [
        'biz_order_no' => $order['biz_order_no'],
        'pay_order_no' => $order['pay_order_no'],
        'user_id'      => $order['user_id'],
        // 强制为两位小数字符串
        'amount'       => number_format($order['amount'], 2, '.', ''),
        'status'       => 'paid',
        'paid_at'      => $now,
    ];
    $secret = getenv('PAYMENT_NOTIFY_SECRET') ?: '';
    if (strlen($secret) < 32) { throw new RuntimeException('Configure PAYMENT_NOTIFY_SECRET (at least 32 characters) on both systems.'); } // 与业务系统一致
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
    $upd2 = $db->prepare("UPDATE pay_orders SET biz_notified=?, biz_notify_result=? WHERE pay_order_no=?");
    $upd2->bind_param('iss', $biz_notified, $biz_notify_result, $pay_order_no);
    $upd2->execute();
    $upd2->close();
}

echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
?>