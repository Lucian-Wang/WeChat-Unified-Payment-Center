<?php
session_start();
session_unset();
session_destroy();

// 清理 SSO token，本地和根域都要清
setcookie('user_center_token', '', time() - 3600, "/", ".wsx.tax");
setcookie('user_center_token', '', time() - 3600, "/"); // 防止部分浏览器未清

// 跳 SSO中心登出（可选，建议保留）
header("Location: https://user.wsx.tax/admin/logout.php?redirect_uri=https://user.wsx.tax/admin/dashboard.php");
exit;
?>