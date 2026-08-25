<?php
// admin/login.php
require_once '../config.php';

if (isAdminLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $admin['username'];
            header('Location: index.php'); exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<button class="neu-btn theme-toggle" id="themeToggle"
    style="position:fixed;top:16px;right:16px;width:46px;height:46px;border-radius:50%;font-size:1.3rem;z-index:99">
    🌙
</button>

<div class="login-wrap">
    <div class="neu-card login-box">
        <div style="text-align:center;font-size:3rem;margin-bottom:8px">🔐</div>
        <h2>Admin Login</h2>
        <p><?= e(SITE_NAME) ?> Control Panel</p>

        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-row">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="neu-input"
                    placeholder="Enter username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    required autofocus>
            </div>
            <div class="form-row" style="margin-bottom:24px">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="neu-input"
                    placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:14px">
                🚀 Login
            </button>
        </form>

        <div style="text-align:center;margin-top:20px">
            <a href="../index.php" style="color:var(--text-muted);font-size:0.9rem">
                ← Back to Website
            </a>
        </div>

        <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);
            font-size:0.78rem;color:var(--text-muted);text-align:center">
            &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
