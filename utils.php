<?php
function json_resp($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function gen_pay_order_no() {
    return 'PC' . date('YmdHis') . rand(1000,9999);
}
function wx_sign($params, $api_key) {
    ksort($params);
    $stringA = '';
    foreach ($params as $k => $v) if ($v !== '' && !is_null($v)) $stringA .= "$k=$v&";
    $stringSignTemp = $stringA . "key=" . $api_key;
    return strtoupper(md5($stringSignTemp));
}
function arr2xml($params) {
    $xml = "<xml>";
    foreach ($params as $k => $v) $xml .= "<$k><![CDATA[$v]]></$k>";
    $xml .= "</xml>";
    return $xml;
}
function xml2arr($xml) {
    return (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
}