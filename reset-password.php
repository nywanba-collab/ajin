<?php
/**
 * 管理员密码重置工具
 * 使用方式：上传到站点根目录，访问 https://www.posge.com/reset-password.php
 * 使用后请立即删除此文件！
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? 'admin');
    $password = $_POST['password'] ?? 'admin123';

    if (strlen($password) < 6) {
        $msg = '<div style="color:#dc2626;padding:12px;background:#fee2e2;border-radius:8px;margin-bottom:16px">密码至少6位</div>';
    } else {
        try {
            $db = DB::getInstance();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->execute("UPDATE `admin_users` SET `password` = ? WHERE `username` = ?", [$hash, $username]);
            $msg = '<div style="color:#16a34a;padding:12px;background:#dcfce7;border-radius:8px;margin-bottom:16px">✅ 密码已重置成功！用户名：<strong>' . htmlspecialchars($username) . '</strong>，新密码：<strong>' . htmlspecialchars($password) . '</strong></div>';
        } catch (Exception $e) {
            $msg = '<div style="color:#dc2626;padding:12px;background:#fee2e2;border-radius:8px;margin-bottom:16px">❌ 数据库错误：' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理员密码重置</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f1f5f9; }
        .box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 400px; max-width: 90%; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; margin-bottom: 16px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .warn { background: #fef3c7; border: 1px solid #fde68a; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #92400e; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔐 管理员密码重置</h1>
        <p>重置后台登录密码</p>
        <div class="warn">⚠️ 使用后请立即删除本文件！</div>
        <?= $msg ?>
        <form method="POST">
            <label>用户名</label>
            <input type="text" name="username" value="admin">
            <label>新密码</label>
            <input type="text" name="password" value="admin123">
            <button type="submit">重置密码</button>
        </form>
    </div>
</body>
</html>
