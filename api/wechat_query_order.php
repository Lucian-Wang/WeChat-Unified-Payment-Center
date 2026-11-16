<?php
require_once '../db.php';
require_once '../utils.php';


$pay_order_no = $_GET['pay_order_no'] ?? '';
if (!$pay_order_no) json_resp(['code'=>1,'msg'=>'缺少pay_order_no']);
$db = get_db();
$stmt = $db->prepare("SELECT status,paid_at FROM pay_orders WHERE pay_order_no=?");
$stmt->bind_param('s', $pay_order_no);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) json_resp(['code'=>2,'msg'=>'订单不存在']);
json_resp(['code'=>0, 'status'=>$res['status'], 'paid_at'=>$res['paid_at']]);