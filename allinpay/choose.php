<?php
$biz_sys = $_GET['biz_sys'] ?? $_POST['biz_sys'] ?? '';
$biz_order_no = $_GET['biz_order_no'] ?? $_POST['biz_order_no'] ?? '';
$user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$amount = $_GET['amount'] ?? $_POST['amount'] ?? '';
$subject = $_GET['subject'] ?? $_POST['subject'] ?? '';
$notify_url = $_GET['notify_url'] ?? $_POST['notify_url'] ?? '';
$return_url = $_GET['return_url'] ?? $_POST['return_url'] ?? '';
$extra = $_GET['extra'] ?? $_POST['extra'] ?? '';

if (!$biz_sys || !$biz_order_no || !$user_id || !$amount || !$subject) {
    die('参数不完整，无法支付。');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>通联收银台 - 选择支付方式</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {background: #f5f7fa;}
    .container {
        max-width: 480px; margin: 60px auto 0 auto; background: #fff;
        border-radius: 26px; box-shadow: 0 6px 36px rgba(49,131,244,0.09);
        padding: 48px 40px 48px 40px; text-align: center;
    }
    .title {font-size: 32px; color: #3784f5; margin-bottom: 30px; font-weight: 700;}
    .info {text-align:left; margin-bottom:32px; font-size: 18px; line-height: 2; color: #222;}
    .info b {color: #3784f5; font-size: 21px;}
    .pay-btns {
        display: flex; justify-content: space-between; margin: 36px 0 0 0; gap:22px;
    }
    .pay-btn {
        flex: 1;
        margin: 0 5px;
        padding: 18px 0 10px 0;
        background: #f7fafd;
        border-radius: 16px;
        border: 2.5px solid transparent;
        cursor: pointer;
        font-size: 20px;
        font-weight: 600;
        color: #222;
        transition: box-shadow .15s, border .15s, background .15s;
        outline: none;
        box-shadow: 0 2px 10px #eaf1fe;
        letter-spacing: 2px;
        position: relative;
    }
    .pay-btn.wx     {border-left: 5px solid #7BC144;}
    .pay-btn.zfb    {border-left: 5px solid #1677FF;}
    .pay-btn.ysf    {border-left: 5px solid #ED3444;}
    .pay-btn:hover, .pay-btn:active {
        border-color: #3784f5; background: #eaf4ff; color: #1677FF;
    }
    @media (max-width:600px){
        .container{max-width:100vw;padding:18px 2vw;}
        .title{font-size:22px;}
        .pay-btns{gap:8px;}
        .pay-btn{min-width:0;padding:11px 0 6px 0;font-size:16px;}
    }
    </style>
</head>
<body>
<div class="container">
    <div class="title">请选择支付方式</div>
    <div class="info">
        订单号：<?=htmlspecialchars($biz_order_no)?><br>
        商品：<?=htmlspecialchars($subject)?><br>
        金额：<b><?=htmlspecialchars($amount)?></b> 元
    </div>
    <form id="payForm" method="post" action="create.php">
        <input type="hidden" name="biz_sys" value="<?=htmlspecialchars($biz_sys)?>">
        <input type="hidden" name="biz_order_no" value="<?=htmlspecialchars($biz_order_no)?>">
        <input type="hidden" name="user_id" value="<?=htmlspecialchars($user_id)?>">
        <input type="hidden" name="amount" value="<?=htmlspecialchars($amount)?>">
        <input type="hidden" name="subject" value="<?=htmlspecialchars($subject)?>">
        <input type="hidden" name="notify_url" value="<?=htmlspecialchars($notify_url)?>">
        <input type="hidden" name="return_url" value="<?=htmlspecialchars($return_url)?>">
        <input type="hidden" name="extra" value="<?=htmlspecialchars($extra)?>">
        <input type="hidden" name="paytype" id="paytype" value="">
        <div class="pay-btns">
            <button type="button" class="pay-btn wx" data-paytype="W01">微信</button>
            <button type="button" class="pay-btn zfb" data-paytype="A01">支付宝</button>
            <button type="button" class="pay-btn ysf" data-paytype="U01">云闪付</button>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('.pay-btn').forEach(function(btn){
    btn.onclick = function() {
        document.getElementById('paytype').value = this.getAttribute('data-paytype');
        document.getElementById('payForm').submit();
    };
});
</script>
</body>
</html>