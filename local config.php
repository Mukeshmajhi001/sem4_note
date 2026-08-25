<?php
// =============================================
// config.php — Database & Site Configuration
// =============================================

// Auto-detect environment: localhost (XAMPP) vs live server (InfinityFree)

// ---------- LOCAL (XAMPP) ----------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'notes_website');
// Keep the current local host/port and point to this project's real folder.
// The old placeholder made local PDF URLs resolve to /your-project-folder.
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/notes-website');


define('SITE_NAME', 'Mks-75 Note\'s');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB

if (session_status() === PHP_SESSION_NONE) session_start();

// PDO Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;background:#1a1a2e;color:#ff6b6b;text-align:center;">
        <h2>⚠️ Database Connection Failed</h2>
        <p>Please check your database credentials in <code>config.php</code></p>
        <small>' . htmlspecialchars($e->getMessage()) . '</small>
    </div>');
}

function isAdminLoggedIn()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}
function formatFileSize($bytes)
{
    if (!$bytes) return 'Unknown';
    $sz = ['B', 'KB', 'MB', 'GB'];
    $f  = (int) floor(log($bytes, 1024));
    return round($bytes / pow(1024, $f), 1) . ' ' . $sz[$f];
}
function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
