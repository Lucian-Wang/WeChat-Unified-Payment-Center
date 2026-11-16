<?php
require_once 'admin_auth.php'; // 统一后台登录校验
require_once '../db.php';

$db = get_db();
$pay_order_no = $_GET['pay_order_no'] ?? '';
if (!$pay_order_no) die('缺少pay_order_no');
$stmt = $db->prepare("SELECT * FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

function h($s) {return htmlspecialchars($s);}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>支付订单详情</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', 'Microsoft YaHei', sans-serif;
            background: #f8f9fb;
            margin: 0;
            padding: 0;
        }
        .main {
            max-width: 900px;
            margin: 38px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            padding: 36px 34px 24px 34px;
        }
        h2 {
            letter-spacing: 1px;
            margin-bottom: 24px;
            color: #222;
        }
        table {
            border-collapse:collapse;
            width: 100%;
            background: #fff;
            font-size: 15px;
            margin-bottom: 22px;
        }
        th,td{
            border:1px solid #e6e9ef;
            padding:10px 12px;
            text-align:left;
        }
        th{
            background:#f5f7fa;
            color:#222;
            font-weight:600;
            width:180px;
        }
        td{background:#fff;}
        .tag1{color:#26ae60;font-weight:600;}
        .tag0{color:#b1b1b1;font-weight:600;}
        .fail{color:#e74c3c;}
        details summary {cursor:pointer;color:#1687e0;}
        pre {
            background: #f7fafd;
            border-radius: 6px;
            padding: 18px;
            font-size: 13px;
            margin: 6px 0 0 0;
            white-space: pre-wrap;
        }
        a.btn {
            display:inline-block;
            background: #409eff;
            color:#fff;
            padding: 8px 26px;
            border-radius: 4px;
            text-decoration:none;
            font-size: 15px;
            margin-top: 18px;
            transition: background 0.2s;
        }
        a.btn:hover {background:#237ad3;}
        @media (max-width: 700px) {
            .main {padding: 7px;}
            table, th, td {font-size: 13px; padding: 5px 4px;}
            th {width:100px;}
        }
    </style>
</head>
<body>
<div class="main">
    <h2>支付订单详情</h2>
    <table>
        <?php foreach($order as $k=>$v): ?>
        <tr>
            <th><?=h($k)?></th>
            <td style="max-width:800px;word-break:break-all;">
                <?php if($k === 'wx_notified'): ?>
                    <span class="tag<?=intval($v)?>"><?= $v ? '已收到微信回调' : '未收到微信回调' ?></span>
                <?php elseif($k === 'biz_notified'): ?>
                    <span class="tag<?=intval($v)?>"><?= $v ? '已通知业务系统' : '未通知业务系统' ?></span>
                <?php elseif($k === 'biz_notify_result'): ?>
                    <span class="<?= ($order['biz_notified']==0 && $v) ? 'fail' : '' ?>">
                        <?= h($v) ?>
                    </span>
                <?php elseif($k === 'wx_notify_raw' && $v): ?>
                    <details>
                        <summary>点击展开微信原始回调内容</summary>
                        <pre><?=h($v)?></pre>
                    </details>
                <?php else: ?>
                    <?=h(is_string($v)?$v:json_encode($v,JSON_UNESCAPED_UNICODE))?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a class="btn" href="index.php">返回订单列表</a>
</div>
</body>
</html>