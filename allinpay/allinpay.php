<?php
// 配置参数（请根据你的通联账号实际设置）
$appid      = '00356343';
$cusid      = '5644610893141KE';
$priKeyPath = '/www/wwwroot/pay.wsx.tax/certs/merchant_rsa_private.pem';
$pubKeyPath = '/www/wwwroot/pay.wsx.tax/certs/allinpay_public.pem';

/**
 * 通联支付下单
 * @param string $paytype W01(微信)/A01(支付宝)/U01(云闪付)
 * @param int $amount 单位:分
 * @param string $order_no 平台订单号
 * @param string $subject 商品描述
 * @return array [code, payinfo, data, msg]
 */
function allinpay_create_order($paytype, $amount, $order_no, $subject) {
    global $appid, $cusid, $priKeyPath;
    $params = [
        'appid'     => $appid,
        'cusid'     => $cusid,
        'paytype'   => $paytype,
        'randomstr' => bin2hex(random_bytes(8)),
        'trxamt'    => $amount,
        'reqsn'     => $order_no,
        'body'      => $subject,
        'signtype'  => 'RSA',
        'version'   => '11'
    ];
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null && $k != 'sign') $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    $priKey = file_get_contents($priKeyPath);
    $privateKey = openssl_get_privatekey($priKey);
    if (!$privateKey) return ['code'=>-1, 'msg'=>'私钥加载失败'];
    $signature = '';
    openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    unset($privateKey);
    $params['sign'] = base64_encode($signature);

    $ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/pay');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!$data) return ['code'=>-2, 'msg'=>'通联返回非法', 'raw'=>$response];

    if (isset($data['retcode']) && $data['retcode'] == 'SUCCESS' && isset($data['payinfo'])) {
        return ['code'=>0, 'payinfo'=>$data['payinfo'], 'data'=>$data];
    }
    return ['code'=>-3, 'msg'=>($data['retmsg'] ?? '通联下单失败'), 'raw'=>$data];
}

/**
 * 通联支付查单
 * @param string $order_no 平台订单号
 * @return array
 */
function allinpay_query_order($order_no) {
    global $appid, $cusid, $priKeyPath;
    $params = [
        'appid'     => $appid,
        'cusid'     => $cusid,
        'reqsn'     => $order_no,
        'randomstr' => bin2hex(random_bytes(8)),
        'signtype'  => 'RSA',
        'version'   => '11'
    ];
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null && $k != 'sign') $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    $priKey = file_get_contents($priKeyPath);
    $privateKey = openssl_get_privatekey($priKey);
    if (!$privateKey) return ['retcode'=>'FAIL', 'retmsg'=>'私钥加载失败'];
    $signature = '';
    openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    unset($privateKey);
    $params['sign'] = base64_encode($signature);

    $ch = curl_init('https://vsp.allinpay.com/apiweb/unitorder/query');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    // 建议这里加上验签（略），可直接用
    return $data;
}