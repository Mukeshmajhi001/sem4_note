<?php
// admin/index.php — Dashboard
require_once '../config.php';
requireAdmin();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM folders)                    AS folder_count,
        (SELECT COUNT(*) FROM files)                      AS file_count,
        (SELECT COUNT(*) FROM files WHERE is_locked = 1)  AS locked_count,
        (SELECT COUNT(*) FROM files WHERE is_locked = 0)  AS unlocked_count,
        (SELECT COALESCE(SUM(downloads),0) FROM files)    AS total_downloads
")->fetch();

$recent = $pdo->query("
    SELECT f.*, fo.name AS folder_name
    FROM files f JOIN folders fo ON fo.id = f.folder_id
    ORDER BY f.created_at DESC LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(SITE_NAME) ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">

        <div class="admin-header">
            <h1>📊 Dashboard</h1>
            <button class="neu-btn theme-toggle" id="themeToggle"
                style="width:44px;height:44px;border-radius:50%;font-size:1.2rem">🌙</button>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards">
            <div class="neu-card stat-card fade-in">
                <div class="stat-number"><?= $stats['folder_count'] ?></div>
                <div class="stat-label">📂 Subjects</div>
            </div>
            <div class="neu-card stat-card fade-in fade-in-1">
                <div class="stat-number"><?= $stats['file_count'] ?></div>
                <div class="stat-label">📄 Total Notes</div>
            </div>
            <div class="neu-card stat-card fade-in fade-in-1">
                <div class="stat-number" style="color:#22c55e;-webkit-text-fill-color:#22c55e">
                    <?= $stats['unlocked_count'] ?>
                </div>
                <div class="stat-label">🔓 Accessible</div>
            </div>
            <div class="neu-card stat-card fade-in fade-in-2">
                <div class="stat-number" style="color:var(--danger);-webkit-text-fill-color:var(--danger)">
                    <?= $stats['locked_count'] ?>
                </div>
                <div class="stat-label">🔒 Locked</div>
            </div>
            <div class="neu-card stat-card fade-in fade-in-2">
                <div class="stat-number"><?= $stats['total_downloads'] ?></div>
                <div class="stat-label">⬇️ Downloads</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:30px">
            <a href="folders.php" style="text-decoration:none">
                <div class="neu-card" style="padding:20px;display:flex;align-items:center;gap:14px">
                    <span style="font-size:2rem">📂</span>
                    <div>
                        <div style="font-weight:700">Manage Subjects</div>
                        <div style="font-size:0.8rem;color:var(--text-muted)">Create, edit, delete folders</div>
                    </div>
                </div>
            </a>
            <a href="files.php" style="text-decoration:none">
                <div class="neu-card" style="padding:20px;display:flex;align-items:center;gap:14px">
                    <span style="font-size:2rem">📤</span>
                    <div>
                        <div style="font-weight:700">Upload Notes</div>
                        <div style="font-size:0.8rem;color:var(--text-muted)">PDFs, links, or both</div>
                    </div>
                </div>
            </a>
            <a href="files.php?locked=1" style="text-decoration:none">
                <div class="neu-card" style="padding:20px;display:flex;align-items:center;gap:14px">
                    <span style="font-size:2rem">🔒</span>
                    <div>
                        <div style="font-weight:700">Locked Notes</div>
                        <div style="font-size:0.8rem;color:var(--text-muted)"><?= $stats['locked_count'] ?> note(s) locked</div>
                    </div>
                </div>
            </a>
            <a href="../index.php" target="_blank" style="text-decoration:none">
                <div class="neu-card" style="padding:20px;display:flex;align-items:center;gap:14px">
                    <span style="font-size:2rem">🌐</span>
                    <div>
                        <div style="font-weight:700">View Website</div>
                        <div style="font-size:0.8rem;color:var(--text-muted)">Public-facing view</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Files -->
        <div class="neu-card form-section fade-in">
            <h2>🕓 Recently Added Notes</h2>
            <?php if (empty($recent)): ?>
            <p style="color:var(--text-muted)">No files yet. Upload some notes first.</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Title</th><th>Subject</th><th>Type</th><th>Status</th><th>Downloads</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $f): ?>
                <tr>
                    <td data-label="Title" style="font-weight:700"><?= e($f['title']) ?></td>
                    <td data-label="Subject"><?= e($f['folder_name']) ?></td>
                    <td data-label="Type">
                        <span class="meta-badge">
                            <?= $f['file_type']==='pdf' ? '📄 PDF' : ($f['file_type']==='link' ? '🔗 Link' : '📄+🔗') ?>
                        </span>
                    </td>
                    <td data-label="Status">
                        <?php if ($f['is_locked']): ?>
                        <span class="lock-badge locked">🔒 Locked</span>
                        <?php else: ?>
                        <span class="lock-badge unlocked">🔓 Available</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Downloads">⬇️ <?= $f['downloads'] ?></td>
                    <td data-label="Date" style="color:var(--text-muted);font-size:.85rem">
                        <?= date('d M Y', strtotime($f['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
