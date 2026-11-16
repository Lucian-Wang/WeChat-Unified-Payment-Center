<?php
session_start();

// 1. 获取token（优先GET带回，其次cookie/session）
$token = $_GET['token'] ?? ($_COOKIE['user_center_token'] ?? ($_SESSION['user_center_token'] ?? ''));

// 2. JWT Payload 解析（不校验签名，仅本地解码）
function jwt_decode($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    $payload = $parts[1];
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4); // 补齐base64长度
    $decoded = base64_decode(strtr($payload, '-_', '+/'));
    return json_decode($decoded, true);
}

if ($token) {
    $payload = jwt_decode($token);

    if (
        $payload
        && isset($payload['roles'])
        && is_array($payload['roles'])
        && in_array('admin', $payload['roles'])
        && (!isset($payload['exp']) || time() < $payload['exp'])
    ) {
        // 3. 有管理员权限，记录token并跳后台首页
        $_SESSION['user_center_token'] = $token;
        setcookie('user_center_token', $token, time()+3600, "/");
        header('Location: index.php');
        exit;
    } else {
        // 4. 没有权限或token无效，清除token并提示
        setcookie('user_center_token', '', time()-3600, "/");
        $_SESSION['user_center_token'] = '';
        $error = '无管理员权限，无法访问后台！';
    }
} else {
    // 5. 没有token，跳转到统一登录页，redirect参数指向自己
    $redirect_url = 'https://pay.wsx.tax/admin/login.php';
    header('Location: https://user.wsx.tax/admin/login.php?redirect=' . urlencode($redirect_url));
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>支付中心后台登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:400px">
  <div class="card p-4 shadow">
    <h3 class="mb-4 text-center">支付中心后台登录</h3>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?=$error?></div><?php endif;?>
    <div class="alert alert-info">
      请通过统一用户中心进行登录。
    </div>
    <a href="https://user.wsx.tax/admin/login.php?redirect=<?=urlencode('https://pay.wsx.tax/admin/login.php')?>" class="btn btn-primary w-100">去用户中心登录</a>
  </div>
</div>
</body>
</html>