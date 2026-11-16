<?php
require_once 'admin_auth.php'; // 统一后台登录校验

require_once '../db.php';
$db = get_db();

$where = '1=1';
$params = [];
$types = '';

if (!empty($_GET['pay_order_no'])) {
    $where .= " AND pay_order_no=?";
    $params[] = $_GET['pay_order_no'];
    $types .= 's';
}
if (!empty($_GET['biz_order_no'])) {
    $where .= " AND biz_order_no=?";
    $params[] = $_GET['biz_order_no'];
    $types .= 's';
}
if (!empty($_GET['biz_sys'])) {
    $where .= " AND biz_sys=?";
    $params[] = $_GET['biz_sys'];
    $types .= 's';
}
if (!empty($_GET['status'])) {
    $where .= " AND status=?";
    $params[] = $_GET['status'];
    $types .= 's';
}
if (!empty($_GET['user_id'])) {
    $where .= " AND user_id=?";
    $params[] = $_GET['user_id'];
    $types .= 'i';
}
// 新增回调状态筛选
if (isset($_GET['wx_notified']) && $_GET['wx_notified'] !== '') {
    $where .= " AND wx_notified=?";
    $params[] = intval($_GET['wx_notified']);
    $types .= 'i';
}
if (isset($_GET['biz_notified']) && $_GET['biz_notified'] !== '') {
    $where .= " AND biz_notified=?";
    $params[] = intval($_GET['biz_notified']);
    $types .= 'i';
}

// 简易分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page-1)*$page_size;

$sql = "SELECT * FROM pay_orders WHERE $where ORDER BY id DESC LIMIT $offset, $page_size";
$stmt = $db->prepare("SELECT count(*) as total FROM pay_orders WHERE $where");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function h($s) {return htmlspecialchars($s);}
function get_pay_type_name($row) {
    // pay_type兼容空（原生微信），优先pay_type，无则判断订单号特征或二维码字段
    $pay_type = $row['pay_type'] ?? '';
    $qrcode_url = $row['wx_qrcode_url'] ?? '';
    if ($pay_type == 'W01' || $pay_type == 'WECHAT' || ($pay_type=='' && strpos($qrcode_url, 'weixin')!==false)) return '微信';
    if ($pay_type == 'A01' || $pay_type == 'ALIPAY' || ($pay_type=='' && strpos($qrcode_url, 'alipay')!==false)) return '支付宝';
    if ($pay_type == 'U01' || $pay_type == 'UNIONPAY' || ($pay_type=='' && strpos($qrcode_url, 'unionpay')!==false)) return '云闪付';
    if ($pay_type) return $pay_type;
    return '微信'; // 默认老订单为微信
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>支付中心-订单日志</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', 'Microsoft YaHei', sans-serif;
            background: #f8f9fb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1300px;
            margin: 32px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            padding: 32px 28px 24px 28px;
            position: relative;
        }
        h2 {
            letter-spacing: 2px;
            margin-bottom: 18px;
            color: #222;
        }
        form {
            margin-bottom: 18px;
            padding: 12px 0 6px 0;
            background: #fafbfc;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        form input,
        form select {
            padding: 4px 10px;
            border: 1px solid #bfc6cf;
            border-radius: 4px;
            background: #fff;
            margin-right: 10px;
            font-size: 14px;
        }
        form button {
            background: #409eff;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        form button:hover {
            background: #237ad3;
        }
        table {
            border-collapse:collapse;
            width: 100%;
            margin-top: 8px;
            background: #fff;
            font-size: 15px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        }
        th,td{
            border:1px solid #e6e9ef;
            padding:8px 8px;
            text-align:center;
        }
        th{
            background:#f5f7fa;
            color:#222;
            font-weight:600;
        }
        .tag1{color:#26ae60;font-weight:600;}
        .tag0{color:#b1b1b1;font-weight:600;}
        .nowrap{white-space:nowrap;}
        .fail{color:#e74c3c;}
        tr:nth-child(even) {background:#fafbfc;}
        tr:hover {background:#f0f7ff;}
        a {
            color: #1687e0;
            text-decoration: none;
            transition: color 0.15s;
        }
        a:hover {color: #0d60b4;}
        .page {
            margin-top: 20px;
            text-align: center;
        }
        .page a,
        .page b {
            display: inline-block;
            padding: 6px 10px;
            margin: 0 3px;
            border-radius: 3px;
            font-size: 15px;
        }
        .page a {
            border: 1px solid #d0dde6;
            background: #fff;
            color: #1687e0;
            text-decoration: none;
            transition: background 0.13s;
        }
        .page a:hover {
            background: #eaf3ff;
            border-color: #85b7e9;
        }
        .page b {
            background: #1687e0;
            color: #fff;
            border: none;
        }
        @media (max-width: 1100px) {
            .container {padding: 15px;}
            table {font-size: 13px;}
            th,td{padding:4px 3px;}
        }
        @media (max-width: 700px) {
            .container {
                padding: 3px;
            }
            table, th, td {
                font-size: 12px;
                padding: 2px 1px;
            }
        }
        .logout-btn {
            position: absolute;
            top: 32px;
            right: 38px;
            z-index: 10;
            color: #fff;
            background: #e74c3c;
            padding: 7px 18px;
            border-radius: 4px;
            font-size: 15px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="logout.php" class="logout-btn">退出登录</a>
    <h2>支付中心 订单日志</h2> 
    <form method="get">
        订单号: <input name="pay_order_no" value="<?=h($_GET['pay_order_no']??'')?>"/>
        业务订单号: <input name="biz_order_no" value="<?=h($_GET['biz_order_no']??'')?>"/>
        业务系统: <input name="biz_sys" value="<?=h($_GET['biz_sys']??'')?>"/>
        用户ID: <input name="user_id" value="<?=h($_GET['user_id']??'')?>"/>
        状态:
        <select name="status">
            <option value="">全部</option>
            <option value="unpaid"<?=(@$_GET['status']=='unpaid'?' selected':'')?>>未支付</option>
            <option value="paid"<?=(@$_GET['status']=='paid'?' selected':'')?>>已支付</option>
        </select>
        微信回调:
        <select name="wx_notified">
            <option value="">全部</option>
            <option value="1"<?=(@$_GET['wx_notified']==='1'?' selected':'')?>>成功</option>
            <option value="0"<?=(@$_GET['wx_notified']==='0'?' selected':'')?>>未收到</option>
        </select>
        业务回调:
        <select name="biz_notified">
            <option value="">全部</option>
            <option value="1"<?=(@$_GET['biz_notified']==='1'?' selected':'')?>>成功</option>
            <option value="0"<?=(@$_GET['biz_notified']==='0'?' selected':'')?>>失败/未通知</option>
        </select>
        <button type="submit">查询</button>
    </form>
    <table>
        <tr>
            <th>ID</th>
            <th>支付订单号</th>
            <th>支付方式</th>
            <th>业务系统</th>
            <th>业务订单号</th>
            <th>用户ID</th>
            <th>金额</th>
            <th>商品</th>
            <th>状态</th>
            <th class="nowrap">微信回调</th>
            <th class="nowrap">业务回调</th>
            <th>支付时间</th>
            <th>操作</th>
        </tr>
        <?php foreach($list as $row): ?>
        <tr>
            <td><?=h($row['id'])?></td>
            <td><?=h($row['pay_order_no'])?></td>
            <td><?=h(get_pay_type_name($row))?></td>
            <td><?=h($row['biz_sys'])?></td>
            <td><?=h($row['biz_order_no'])?></td>
            <td><?=h($row['user_id'])?></td>
            <td><?=h($row['amount'])?></td>
            <td><?=h($row['subject'])?></td>
            <td><?=h($row['status'])?></td>
            <td class="nowrap tag<?=$row['wx_notified']?>"><?= $row['wx_notified'] ? '已收到' : '未收到' ?></td>
            <td class="nowrap tag<?=$row['biz_notified']?>"><?= $row['biz_notified'] ? '已通知' : '未通知' ?></td>
            <td><?=h($row['paid_at'])?></td>
            <td>
                <a href="detail.php?pay_order_no=<?=h($row['pay_order_no'])?>">详情</a>
                <?php if($row['status']=='paid' && !$row['biz_notified']): ?>
                    | <a href="retry_notify.php?pay_order_no=<?=h($row['pay_order_no'])?>" onclick="return confirm('确定要重新通知业务系统吗？')">重新通知</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="page">
        <?php
        $total_page = ceil($total/$page_size);
        for($i=1;$i<=$total_page;$i++) {
            if($i==$page) echo "<b>[$i]</b> ";
            else {
                $q = $_GET; $q['page']=$i;
                echo '<a href="?'.http_build_query($q).'">['.$i.']</a> ';
            }
        }
        ?>
    </div>
</div>
</body>
</html>