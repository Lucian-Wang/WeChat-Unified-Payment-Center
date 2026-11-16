<?php
session_start();
$token = $_SESSION['user_center_token'] ?? $_COOKIE['user_center_token'] ?? '';
function jwt_decode($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    $payload = $parts[1];
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
    $decoded = base64_decode(strtr($payload, '-_', '+/'));
    return json_decode($decoded, true);
}
$payload = $token ? jwt_decode($token) : null;
if (
    !$payload ||
    !isset($payload['roles']) ||
    !is_array($payload['roles']) ||
    !in_array('admin', $payload['roles']) ||
    (isset($payload['exp']) && time() >= $payload['exp'])
) {
    setcookie('user_center_token', '', time()-3600, "/");
    $_SESSION['user_center_token'] = '';
    header('Location: login.php');
    exit;
}