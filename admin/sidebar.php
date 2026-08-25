<?php
// admin/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        📚 Mks-75 Note's<br>
        <span style="font-size:0.7rem;font-weight:400;color:var(--text-muted)">Admin Panel</span>
    </div>

    <a href="index.php"   class="sidebar-link <?= $current==='index.php'   ? 'active':'' ?>">
        <span class="icon">📊</span> Dashboard
    </a>
    <a href="folders.php" class="sidebar-link <?= $current==='folders.php' ? 'active':'' ?>">
        <span class="icon">📂</span> Subjects / Folders
    </a>
    <a href="files.php"   class="sidebar-link <?= $current==='files.php'   ? 'active':'' ?>">
        <span class="icon">📄</span> Notes / Files
    </a>

    <div style="flex:1"></div>

    <a href="../index.php" target="_blank" class="sidebar-link">
        <span class="icon">🌐</span> View Website
    </a>
    <a href="logout.php" class="sidebar-link" style="color:var(--danger)">
        <span class="icon">🚪</span> Logout
    </a>

    <div style="padding:10px 14px;font-size:0.78rem;color:var(--text-muted);
        margin-top:8px;border-top:1px solid var(--border)">
        👤 <?= e($_SESSION['admin_username'] ?? 'Admin') ?>
    </div>
</aside>
