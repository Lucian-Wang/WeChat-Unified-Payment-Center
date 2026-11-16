<?php
require_once '../db.php';
require_once '../config.php';
require_once '../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_resp(['code'=>1,'msg'=>'仅支持POST']);
$params = json_decode(file_get_contents('php://input'), true);

$biz_sys = $params['biz_sys'] ?? '';
$biz_order_no = $params['biz_order_no'] ?? '';
$user_id = $params['user_id'] ?? 0;
$amount = $params['amount'] ?? 0;
$subject = $params['subject'] ?? '';
$notify_url = $params['notify_url'] ?? '';
$return_url = $params['return_url'] ?? ''; // 新增
$extra = isset($params['extra']) ? json_encode($params['extra'], JSON_UNESCAPED_UNICODE) : '';

if (!$biz_sys || !$biz_order_no || !$user_id || !$amount || !$subject) json_resp(['code'=>2, 'msg'=>'参数不完整']);

$pay_order_no = gen_pay_order_no();
$db = get_db();

// 检查业务订单是否已存在
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE biz_sys=? AND biz_order_no=? LIMIT 1");
$stmt->bind_param('ss', $biz_sys, $biz_order_no);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) json_resp(['code'=>3, 'msg'=>'业务订单已存在']);
$stmt->close();

// 下单到微信
$cfg = $config['wechat'];
$total_fee = intval(round($amount * 100));
$wx_params = [
    'appid'            => $cfg['appid'],
    'mch_id'           => $cfg['mch_id'],
    'nonce_str'        => strtoupper(md5(uniqid(mt_rand(), true))),
    'body'             => $subject,
    'out_trade_no'     => $pay_order_no,
    'total_fee'        => $total_fee,
    'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'notify_url'       => $cfg['notify_url'],
    'trade_type'       => 'NATIVE',
];
$wx_params['sign'] = wx_sign($wx_params, $cfg['api_key']);
$xml = arr2xml($wx_params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/pay/unifiedorder");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
curl_close($ch);

$wx_res = xml2arr($response);
if ($wx_res['return_code'] !== 'SUCCESS' || $wx_res['result_code'] !== 'SUCCESS') {
    json_resp(['code'=>4, 'msg'=>'微信下单失败', 'detail'=>$wx_res]);
}

$qrcode_url = $wx_res['code_url'];

// 保存订单（增加 return_url 字段）
$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("INSERT INTO pay_orders (pay_order_no, biz_sys, biz_order_no, user_id, amount, subject, status, notify_url, return_url, extra, wx_qrcode_url, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    'sssissssssss',
    $pay_order_no,   // s
    $biz_sys,        // s
    $biz_order_no,   // s
    $user_id,        // i
    $amount,         // s
    $subject,        // s
    $notify_url,     // s
    $return_url,     // s 新增
    $extra,          // s
    $qrcode_url,     // s
    $now,            // s
    $now             // s
);
$stmt->execute();

// 返回 pay_url 时拼上 return_url 参数（如果有）
$pay_url = 'https://pay.wsx.tax/wechat/pay/index.php?pay_order_no=' . urlencode($pay_order_no);
if ($return_url) {
    $pay_url .= '&return_url=' . urlencode($return_url);
}

json_resp([
    'code'=>0,
    'msg'=>'ok',
    'data'=>[
        'pay_order_no' => $pay_order_no,
        'qrcode_url'   => $qrcode_url,
        'pay_url'      => $pay_url
    ]
]);