<?php
/**
 * 通联支付：API下单接口
 * POST JSON参数：biz_sys, biz_order_no, user_id, amount, subject, paytype, notify_url, return_url, extra
 * 业务系统需确保biz_order_no全局唯一！
 * 返回JSON：{ code, msg, data: { pay_order_no, qrcode_url, pay_url } }
 */
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../allinpay/allinpay.php';

header('Content-Type: application/json; charset=utf-8');
$input = file_get_contents('php://input');
$params = json_decode($input, true);

// 参数解析与校验
$biz_sys     = $params['biz_sys']     ?? '';
$biz_order_no= $params['biz_order_no']?? '';
$user_id     = $params['user_id']     ?? '';
$amount      = $params['amount']      ?? '';
$subject     = $params['subject']     ?? '';
$paytype     = $params['paytype']     ?? '';
$notify_url  = $params['notify_url']  ?? '';
$return_url  = $params['return_url']  ?? '';
$extra       = $params['extra']       ?? '';

if (!$biz_sys || !$biz_order_no || !$user_id || !$amount || !$subject || !$paytype) {
    echo json_encode(['code'=>1, 'msg'=>'参数不完整']); exit;
}

// 幂等：同一业务订单号不可重复下单
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE biz_sys=? AND biz_order_no=? LIMIT 1");
$stmt->bind_param('ss', $biz_sys, $biz_order_no);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    echo json_encode([
        'code'=>0, 'msg'=>'ok',
        'data'=>[
            'pay_order_no' => $row['pay_order_no'],
            'qrcode_url'   => $row['wx_qrcode_url'],
            'pay_url'      => "https://pay.wsx.tax/allinpay/qrcode.php?pay_order_no={$row['pay_order_no']}"
        ]
    ]); exit;
}
$stmt->close();

$pay_order_no = gen_pay_order_no();
$now = date('Y-m-d H:i:s');

// 调通联接口下单
$res = allinpay_create_order($paytype, intval(round($amount*100)), $pay_order_no, $subject);

if (!is_array($res) || $res['code'] !== 0) {
    echo json_encode(['code'=>2, 'msg'=>'通联下单失败: ' . ($res['msg'] ?? '未知错误')]);
    exit;
}
$qrcode_url = $res['payinfo'];
$status = 'unpaid';

// 入库
$stmt = $db->prepare(
    "INSERT INTO pay_orders (pay_order_no, biz_sys, biz_order_no, user_id, amount, subject, status, notify_url, return_url, extra, wx_qrcode_url, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'sssisssssssss',
    $pay_order_no,
    $biz_sys,
    $biz_order_no,
    $user_id,
    $amount,
    $subject,
    $status,
    $notify_url,
    $return_url,
    $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : '',
    $qrcode_url,
    $now,
    $now
);
$stmt->execute();

echo json_encode([
    'code'=>0, 'msg'=>'ok',
    'data'=>[
        'pay_order_no' => $pay_order_no,
        'qrcode_url'   => $qrcode_url,
        'pay_url'      => "https://pay.wsx.tax/allinpay/qrcode.php?pay_order_no={$pay_order_no}"
    ]
]);
exit;