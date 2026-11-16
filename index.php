<?php
// 根目录公开介绍页，不做登录校验和后台跳转
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>支付中心 - pay.wsx.tax</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', 'Microsoft YaHei', sans-serif;
            background: #f8f9fb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 80px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            padding: 40px 34px 34px 34px;
            text-align: center;
        }
        h1 {
            color: #1687e0;
            margin-bottom: 24px;
            font-size: 2.2em;
            letter-spacing: 2px;
        }
        p {
            color: #333;
            font-size: 1.12em;
            line-height: 1.7;
        }
        .tip {
            color: #888;
            font-size: 0.96em;
            margin-top: 24px;
        }
        .admin-link {
            display: inline-block;
            margin-top: 36px;
            padding: 8px 26px;
            background: #409eff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.1em;
            transition: background 0.17s;
        }
        .admin-link:hover {
            background: #237ad3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>支付中心</h1>
        <p>
            欢迎访问 <strong>pay.wsx.tax</strong> 支付中心。<br>
            本系统用于对接、管理各业务系统的支付订单、回调与结算。<br>
            如果您是普通用户，请通过各业务系统下单和支付。<br>
            如需技术支持或商务合作，请联系管理员。
        </p>
        <div class="tip">
            如需访问后台，请通过授权账号登录管理入口。
        </div>
        <a href="/admin/login.php" class="admin-link">后台登录</a>
    </div>
</body>
</html>