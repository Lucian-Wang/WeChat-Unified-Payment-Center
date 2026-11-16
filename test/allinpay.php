<?php
// 生成唯一业务订单号
$biz_order_no = 'BIZ' . date('YmdHis') . rand(1000, 9999);
$amount = '0.01'; // 测试金额
$subject = '测试商品-' . date('His');
$user_id = 123456;
$biz_sys = 'testsaas';

// 支付中心收银台地址
$pay_center_url = '/allinpay/choose.php';

$notify_url = 'https://pay.wsx.tax/api/allinpay_notify.php'; // 业务系统模拟回调地址（可不真实存在）
$return_url = 'https://pay.wsx.tax/api/'; // 支付成功跳回业务页面（可不真实存在）
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>通联SaaS支付聚合测试</title>
</head>
<body>
    <h2>通联支付中心-SaaS示例测试</h2>
    <form method="get" action="<?=$pay_center_url?>">
        <input type="hidden" name="biz_sys" value="<?=htmlspecialchars($biz_sys)?>">
        <input type="hidden" name="biz_order_no" value="<?=htmlspecialchars($biz_order_no)?>">
        <input type="hidden" name="user_id" value="<?=htmlspecialchars($user_id)?>">
        <input type="hidden" name="amount" value="<?=htmlspecialchars($amount)?>">
        <input type="hidden" name="subject" value="<?=htmlspecialchars($subject)?>">
        <input type="hidden" name="notify_url" value="<?=htmlspecialchars($notify_url)?>">
        <input type="hidden" name="return_url" value="<?=htmlspecialchars($return_url)?>">
        <p>订单号：<?=htmlspecialchars($biz_order_no)?></p>
        <p>商品名：<?=htmlspecialchars($subject)?></p>
        <p>金额：<b><?=htmlspecialchars($amount)?></b> 元</p>
        <button type="submit">去支付中心聚合收银台</button>
    </form>
    <hr>
    <p>说明：</p>
    <ol>
        <li>点击按钮会自动跳转到通联聚合收银台（<code>choose.php</code>），无需登录。</li>
        <li>可测试全流程，包括选择支付方式、主动查单、业务回调、结果跳转等。</li>
        <li>如需模拟notify_url/return_url业务端，请将地址换成你自己可接收的url。</li>
    </ol>
</body>
</html>