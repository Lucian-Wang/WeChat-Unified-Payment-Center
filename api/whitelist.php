<?php
// 配置你的白名单IP
$ip_whitelist = [
    '127.0.0.1',
    '::1',
    // 添加你的服务器、业务系统出口IP
    '47.99.88.91', // 例子，替换为实际IP
];

// 判断是否在白名单
function check_ip_whitelist() {
    global $ip_whitelist;
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote_ip, $ip_whitelist)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['code' => -100, 'msg' => 'IP NOT ALLOWED', 'your_ip' => $remote_ip]);
        exit;
    }
}