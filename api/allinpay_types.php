<?php
/**
 * 可用支付方式接口，供聚合收银台或API调用
 * GET/POST均可
 */

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'code'=>0,
    'msg'=>'ok',
    'pay_types'=>[
        ['id'=>'W01', 'name'=>'微信扫码'],
        ['id'=>'A01', 'name'=>'支付宝扫码'],
        ['id'=>'U01', 'name'=>'云闪付扫码']
    ]
]);
exit;