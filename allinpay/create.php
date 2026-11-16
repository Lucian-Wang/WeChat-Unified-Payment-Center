<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils.php'; // 必须引入gen_pay_order_no等工具函数
require_once __DIR__ . '/allinpay.php'; // 必须有allinpay_create_order函数

$biz_sys = $_POST['biz_sys'] ?? '';
$biz_order_no = $_POST['biz_order_no'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$amount = $_POST['amount'] ?? '';
$subject = $_POST['subject'] ?? '';
$notify_url = $_POST['notify_url'] ?? '';
$return_url = $_POST['return_url'] ?? '';
$extra = $_POST['extra'] ?? '';
$paytype = $_POST['paytype'] ?? '';

// 基本参数校验
if (!$biz_sys || !$biz_order_no || !$user_id || !$amount || !$subject || !$paytype) {
    die('参数不完整，无法支付。');
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE biz_sys=? AND biz_order_no=? LIMIT 1");
$stmt->bind_param('ss', $biz_sys, $biz_order_no);
$stmt->execute();
$exist_order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exist_order) {
    // 如果订单已存在，判断状态
    if ($exist_order['status'] === 'paid') {
        die('该订单已支付，请勿重复支付。');
    } else {
        // 未支付则直接跳转现有二维码页（并带上paytype参数）
        header('Location: qrcode.php?pay_order_no=' . urlencode($exist_order['pay_order_no']) . '&paytype=' . urlencode($exist_order['pay_type'] ?? $paytype));
        exit;
    }
}

// 不存在则新建订单
$pay_order_no = gen_pay_order_no();
$now = date('Y-m-d H:i:s');

// 调用通联下单接口
$res = allinpay_create_order($paytype, intval(round($amount*100)), $pay_order_no, $subject);
if (!is_array($res) || $res['code'] !== 0) {
    die('通联下单失败：' . htmlspecialchars($res['msg'] ?? '未知错误'));
}
$qrcode_url = $res['payinfo'];
$status = 'unpaid';

// 保存订单，增加pay_type字段
$stmt = $db->prepare(
    "INSERT INTO pay_orders (pay_order_no, biz_sys, biz_order_no, user_id, amount, subject, status, notify_url, return_url, extra, wx_qrcode_url, pay_type, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'sssissssssssss',
    $pay_order_no,
    $biz_sys,
    $biz_order_no,
    $user_id,
    $amount,
    $subject,
    $status,
    $notify_url,
    $return_url,
    $extra,
    $qrcode_url,
    $paytype,
    $now,
    $now
);
$stmt->execute();

// 跳转到二维码页面，带上paytype参数
header('Location: qrcode.php?pay_order_no=' . urlencode($pay_order_no) . '&paytype=' . urlencode($paytype));
exit;