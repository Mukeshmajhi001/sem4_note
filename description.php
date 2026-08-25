<?php
// description.php — Full note description page
require_once 'config.php';

$file_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$file_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT f.*, fo.name AS folder_name, fo.icon AS folder_icon
                       FROM files f
                       JOIN folders fo ON fo.id = f.folder_id
                       WHERE f.id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($file['title']) ?> — <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">📚 Mks-75<span>Note</span></div>
    <div class="navbar-right">
        <a href="admin/login.php" class="nav-link-btn">⚙️ Admin</a>
        <button class="neu-btn theme-toggle" id="themeToggle">🌙</button>
    </div>
</nav>

<main class="container description-page">
    <div class="breadcrumb fade-in">
        <a href="index.php">🏠 Home</a>
        <span>›</span>
        <a href="folder.php?id=<?= (int)$file['folder_id'] ?>">
            <?= e($file['folder_icon']) ?> <?= e($file['folder_name']) ?>
        </a>
        <span>›</span>
        <span><?= e($file['title']) ?></span>
    </div>

    <article class="neu-card description-card fade-in">
        <div class="description-card-icon">📝</div>
        <div class="description-card-content">
            <span class="meta-badge">Full Description</span>
            <h1><?= e($file['title']) ?></h1>
            <p class="description-full"><?= nl2br(e($file['description'])) ?></p>
            <?php if ($file['is_locked']): ?>
            <div class="locked-overlay description-locked">
                <div class="lock-icon">🔒</div>
                <p>This note is currently locked.<br>Contact admin to get access.</p>
            </div>
            <?php else: ?>
            <?php $pdf_url = $file['file_path'] ? SITE_URL . '/uploads/' . $file['file_path'] : ''; ?>
            <div class="description-actions">
                <?php if ($pdf_url): ?>
                <button type="button" class="btn-view" onclick="openPDF('<?= e($pdf_url) ?>', '<?= e(addslashes($file['title'])) ?>')">
                    👁️ View PDF
                </button>
                <?php endif; ?>
                <?php if ($file['external_link']): ?>
                <a class="btn-view" href="<?= e($file['external_link']) ?>" target="_blank" rel="noopener noreferrer">
                    🔗 Open Link
                </a>
                <?php endif; ?>
                <?php if ($file['file_path']): ?>
                <a class="btn-download" href="folder.php?id=<?= (int)$file['folder_id'] ?>&download=<?= (int)$file['id'] ?>">
                    ⬇️ Download PDF
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <a href="folder.php?id=<?= (int)$file['folder_id'] ?>" class="neu-btn description-back">
                ← Back to Notes
            </a>
        </div>
    </article>
</main>

<div class="modal-overlay" id="pdfModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">📄 PDF Viewer</span>
            <button class="neu-btn modal-close" id="modalClose">✕</button>
        </div>
        <div class="modal-body"><iframe id="pdfFrame" allowfullscreen></iframe></div>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> &mdash; All notes are for educational purposes only.
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
