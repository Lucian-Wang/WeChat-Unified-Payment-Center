<?php
require_once '../../db.php';
require_once '../../utils.php';

// 允许 pay_order_no 或 order_no 两种参数方式，兼容旧链接
$pay_order_no = $_GET['pay_order_no'] ?? $_GET['order_no'] ?? '';
if (!$pay_order_no) die('缺少pay_order_no');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) die('订单不存在');

// 获取 return_url：优先 GET 参数，否则用订单表字段
$return_url = $_GET['return_url'] ?? $order['return_url'] ?? '';
$redirect = $_GET['redirect'] ?? ($order['extra'] ? (json_decode($order['extra'], true)['redirect'] ?? '') : '');
$from = $_GET['from'] ?? '';

// 优先级：redirect > return_url > from
if ($order['status'] === 'paid') {
    if ($redirect) {
        header("Location: $redirect");
        exit;
    } elseif ($return_url) {
        header("Location: $return_url");
        exit;
    } elseif ($from) {
        header("Location: $from");
        exit;
    }
    // 友好支付已完成页面
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>支付成功</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
        body {background: #f5f7fa;}
        .result-container {max-width: 420px; margin: 72px auto 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.09); padding: 44px 30px 32px 30px; text-align: center;}
        .success-icon {font-size: 68px; color: #31c76e; margin-bottom: 18px;}
        .success-title {font-size: 24px; color: #31c76e; font-weight: 700; margin-bottom: 8px; letter-spacing: 2px;}
        .success-detail {color: #555; font-size: 16px; margin-bottom: 16px; word-break: break-all;}
        .order-info {color: #888; font-size: 15px; margin-bottom: 18px; word-break: break-all;}
        .btn-main {background: #31c76e; color: #fff; font-size: 17px; padding: 10px 38px; border-radius: 6px; border: none; cursor: pointer; margin: 6px 8px; margin-top: 16px; letter-spacing: 1px; font-weight: 500;}
        .btn-main:hover {background: #26a45b;}
        @media (max-width: 600px) {.result-container {padding: 14px 2vw;}.success-icon{font-size:48px;}.btn-main{font-size:15px;}}
        </style>
        <script>
        function goBackAndRefresh() {
            // 如果 opener 存在，则刷新父窗口并关闭本页
            if (window.opener && window.opener !== window) {
                try { window.opener.location.reload(); } catch(e){}
                setTimeout(function(){ window.close(); }, 800);
            } else if ("<?=addslashes($return_url)?>") {
                window.location.href = "<?=addslashes($return_url)?>";
            } else if ("<?=addslashes($from)?>") {
                window.location.href = "<?=addslashes($from)?>";
            } else if (document.referrer) {
                window.location.replace(document.referrer + (document.referrer.indexOf('?')>-1?'&':'?') + 'paid=1');
            } else {
                window.location.href = '/';
            }
        }
        window.onload = function() {
            setTimeout(goBackAndRefresh, 2000);
        }
        </script>
    </head>
    <body>
        <div class="result-container">
            <div class="success-icon">✔️</div>
            <div class="success-title">支付成功</div>
            <div class="success-detail">您的订单已成功支付！</div>
            <div class="order-info">
                订单号：<?=htmlspecialchars($pay_order_no)?><br>
                商品：<?=htmlspecialchars($order['subject'])?><br>
                金额：<b><?=htmlspecialchars($order['amount'])?></b> 元
            </div>
            <button class="btn-main" onclick="goBackAndRefresh()">返回上一页</button>
            <button class="btn-main" onclick="window.location.href='/'">返回首页</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
$qrcode_url = $order['wx_qrcode_url'];
$amount = $order['amount'];
$subject = $order['subject'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>微信扫码支付</title>
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
</head>
<body>
    <div class="pay-container">
        <div class="pay-title">微信扫码支付</div>
        <div class="qr-area">
           <img src="https://lib.wsx.tax/qrcode.php?data=<?=urlencode($qrcode_url)?>&size=210&margin=2" alt="微信支付二维码">
        </div>
        <div class="pay-tip">请使用微信扫一扫完成支付</div>
        <div class="order-info">
            订单号：<?=htmlspecialchars($pay_order_no)?><br>
            商品：<?=htmlspecialchars($subject)?><br>
            金额：<b><?=htmlspecialchars($amount)?></b> 元
        </div>
    </div>
    <script>
        // 轮询订单状态
        setInterval(function(){
            fetch("/api/wechat_query_order.php?pay_order_no=<?=urlencode($pay_order_no)?>")
            .then(r=>r.json()).then(d=>{
                if(d.status === 'paid'){
                    <?php
                    if ($redirect) {
                        echo "window.location.href='".addslashes($redirect)."';";
                    } elseif ($return_url) {
                        echo "window.location.href='".addslashes($return_url)."';";
                    } elseif ($from) {
                        echo "window.location.href='".addslashes($from)."';";
                    } else {
                    ?>
                    alert('支付成功！即将返回...');
                    if (window.opener && window.opener !== window) {
                        try { window.opener.location.reload(); }catch(e){}
                        setTimeout(function(){window.close();},800);
                    } else if (document.referrer) {
                        window.location.replace(document.referrer + (document.referrer.indexOf('?')>-1?'&':'?') + 'paid=1');
                    } else {
                        window.location.href = '/';
                    }
                    <?php
                    }
                    ?>
                }
            });
        }, 2000);
    </script>
</body>
</html>