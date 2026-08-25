<?php
// index.php — Home Page
require_once 'config.php';

$folders = $pdo->query("
    SELECT f.*, COUNT(fi.id) as file_count
    FROM folders f
    LEFT JOIN files fi ON fi.folder_id = f.id
    GROUP BY f.id ORDER BY f.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> — Study Notes</title>
    <link rel="icon" type="image/png" href="uploads/logo/logo.png">
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">📚 Mks-75<span>Note</span></div>
        <div class="navbar-right">
            <a href="admin/login.php" class="nav-link-btn">⚙️ Admin</a>
            <button class="neu-btn theme-toggle" id="themeToggle" title="Toggle Theme">🌙</button>
        </div>
    </nav>

    <div class="hero fade-in">
        <h1>Your <span class="grad">Study Notes</span><br>All in One Place</h1>
        <p>Browse subjects, view PDFs online, and download your notes anytime.</p>
        <div class="search-wrap fade-in fade-in-1">
            <span class="search-icon">🔍</span>
            <input type="text" class="neu-input" id="folderSearch" placeholder="Search subjects...">
        </div>
    </div>

    <div class="container">
        <div class="section-title fade-in fade-in-2">📂 All Subjects</div>

        <?php if (empty($folders)): ?>
            <div class="empty-state fade-in">
                <div class="icon">📂</div>
                <h3>No Subjects Found</h3>
                <p>Visit the admin panel to create subject folders.</p>
            </div>
        <?php else: ?>
            <div class="folder-grid fade-in fade-in-2">
                <?php foreach ($folders as $folder): ?>
                    <a href="folder.php?id=<?= $folder['id'] ?>" style="text-decoration:none">
                        <div class="neu-card folder-card" style="--folder-color:<?= e($folder['color']) ?>">
                            <span class="folder-icon"><?= e($folder['icon']) ?></span>
                            <span class="folder-name"><?= e($folder['name']) ?></span>
                            <span class="folder-count"><?= $folder['file_count'] ?>
                                note<?= $folder['file_count'] != 1 ? 's' : '' ?></span>
                            <?php if ($folder['description']): ?>
                                <p style="font-size:.78rem;color:var(--text-muted);margin-top:6px;line-height:1.4">
                                    <?= e($folder['description']) ?>
                                </p>
                            <?php endif; ?>
                            <div class="folder-accent-bar"></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PDF Modal -->
    <div class="modal-overlay" id="pdfModal">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title" id="modalTitle">📄 PDF Viewer</span>
                <button class="neu-btn modal-close" id="modalClose">✕</button>
            </div>
            <div class="modal-body">
                <iframe id="pdfFrame" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> &mdash; All notes are for educational purposes only.
    </footer>

    <script src="assets/js/main.js"></script>
</body>

</html>