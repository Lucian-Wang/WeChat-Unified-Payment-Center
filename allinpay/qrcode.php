<?php
require_once __DIR__ . '/../db.php';

$pay_order_no = $_GET['pay_order_no'] ?? '';
if (!$pay_order_no) die('缺少pay_order_no');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) die('订单不存在');

// ---- 新增部分：获取paytype参数 ----
$paytype = $_GET['paytype'] ?? $_POST['paytype'] ?? $order['pay_type'] ?? ''; // 优先用URL参数，其次表里的
$pay_type_names = [
    'W01' => '微信',
    'A01' => '支付宝',
    'U01' => '云闪付',
];
$pay_app_name = $pay_type_names[$paytype] ?? '对应App';
// -----------------------------------

$qrcode_url = $order['wx_qrcode_url'];
$amount = $order['amount'];
$subject = $order['subject'];
$return_url = $order['return_url'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>扫码支付</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {background: #f5f7fa;}
    .pay-container {max-width: 420px;margin: 60px auto 0 auto;background: #fff;border-radius: 14px;box-shadow: 0 2px 12px rgba(0,0,0,0.09);padding: 36px 30px 28px 30px;text-align: center;}
    .pay-title {font-size: 22px;color: #3183f4;margin-bottom: 15px;font-weight: 600;letter-spacing: 2px;}
    .qr-area {padding: 18px 0;}
    .qr-area img {width: 210px;height: 210px;border: 6px solid #e9eff6;border-radius: 10px;background: #fff;}
    .order-info {margin-top: 22px;text-align: left;color: #333;font-size: 16px;line-height: 2;}
    .order-info b {color: #3183f4;font-size: 18px;font-weight: 700;}
    .pay-tip {color: #999;font-size: 14px;margin-top: 18px;margin-bottom: 6px;letter-spacing: 1px;}
    @media (max-width: 600px) {.pay-container {padding: 12px 5vw;}.qr-area img {width: 150px; height: 150px;}}
    </style>
    <script>
    // 轮询查单
    setInterval(function(){
        fetch("../api/allinpay_query_order.php?pay_order_no=<?=urlencode($pay_order_no)?>")
        .then(r=>r.json()).then(d=>{
            if(d.status === 'paid'){
                // 2秒后自动跳转
                setTimeout(function(){
                    <?php if ($return_url): ?>
                        window.location.href="<?=addslashes($return_url)?>";
                    <?php else: ?>
                        window.location.href="/pay_success.php";
                    <?php endif; ?>
                }, 2000);
                document.getElementById('pay-result').innerHTML = '<span style="color:green;font-size:20px;">支付成功！请稍候...</span>';
            }
        });
    }, 2000);
    </script>
</head>
<body>
    <div class="pay-container">
        <div class="pay-title">扫码支付</div>
        <div class="qr-area">
           <img src="https://lib.wsx.tax/qrcode.php?data=<?=urlencode($qrcode_url)?>&size=210&margin=2" alt="支付二维码">
        </div>
        <div id="pay-result" class="pay-tip">请用<?=htmlspecialchars($pay_app_name)?>App扫码完成支付</div>
        <div class="order-info">
            订单号：<?=htmlspecialchars($pay_order_no)?><br>
            商品：<?=htmlspecialchars($subject)?><br>
            金额：<b><?=htmlspecialchars($amount)?></b> 元
        </div>
    </div>
</body>
</html>