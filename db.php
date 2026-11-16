<?php
$config = require __DIR__ . '/config.php';
function get_db() {
    global $config;
    $db = new mysqli(
        $config['db']['host'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['name'],
        $config['db']['port']
    );
    if ($db->connect_error) die('DB连接失败: ' . $db->connect_error);
    $db->set_charset('utf8mb4');
    return $db;
}