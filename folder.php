<?php
// folder.php — Notes inside a subject folder
require_once 'config.php';
$is_locked = false;
$folder_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$folder_id) { header('Location: index.php'); exit; }

// Get folder
$stmt = $pdo->prepare("SELECT * FROM folders WHERE id = ?");
$stmt->execute([$folder_id]);
$folder = $stmt->fetch();
if (!$folder) { header('Location: index.php'); exit; }

// Handle download (increment counter + serve file)
if (isset($_GET['download'])) {
    $fid = (int)$_GET['download'];
    $stmt2 = $pdo->prepare("SELECT * FROM files WHERE id = ? AND folder_id = ?");
    $stmt2->execute([$fid, $folder_id]);
    $dfile = $stmt2->fetch();
    if ($dfile && !$dfile['is_locked']) {
        $pdo->prepare("UPDATE files SET downloads = downloads + 1 WHERE id = ?")->execute([$fid]);
        if ($dfile['file_path'] && file_exists(UPLOAD_DIR . $dfile['file_path'])) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($dfile['file_path']) . '"');
            header('Content-Length: ' . filesize(UPLOAD_DIR . $dfile['file_path']));
            readfile(UPLOAD_DIR . $dfile['file_path']);
            exit;
        } elseif ($dfile['external_link']) {
            header('Location: ' . $dfile['external_link']); exit;
        }
    }
}

// Get all files in folder
$stmt = $pdo->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY created_at DESC");
$stmt->execute([$folder_id]);
$files = $stmt->fetchAll();

$total   = count($files);
$locked  = count(array_filter($files, fn($f) => $f['is_locked']));
$unlocked = $total - $locked;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($folder['name']) ?> — <?= e(SITE_NAME) ?></title>
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

<div class="container" style="padding-top:28px">

    <!-- Breadcrumb -->
    <div class="breadcrumb fade-in">
        <a href="index.php">🏠 Home</a>
        <span>›</span>
        <span><?= e($folder['name']) ?></span>
    </div>

    <!-- Folder Header -->
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px" class="fade-in">
        <div style="font-size:3rem;line-height:1"><?= e($folder['icon']) ?></div>
        <div>
            <h1 style="font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700">
                <?= e($folder['name']) ?>
            </h1>
            <?php if ($folder['description']): ?>
            <p style="color:var(--text-muted);margin-top:4px"><?= e($folder['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Bar -->
    <?php if ($total > 0): ?>
    <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap" class="fade-in fade-in-1">
        <span class="meta-badge" style="font-size:0.82rem;padding:6px 14px">
            📄 <?= $total ?> total note<?= $total != 1 ? 's' : '' ?>
        </span>
        <span class="lock-badge unlocked" style="font-size:0.82rem;padding:6px 14px">
            🔓 <?= $unlocked ?> accessible
        </span>
        <?php if ($locked > 0): ?>
        <span class="lock-badge locked" style="font-size:0.82rem;padding:6px 14px">
            🔒 <?= $locked ?> locked
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="search-wrap fade-in fade-in-1" style="max-width:420px;margin:0 0 24px">
        <span class="search-icon">🔍</span>
        <input type="text" class="neu-input" id="fileSearch" placeholder="Search notes...">
    </div>

    <!-- Files -->
    <?php if (empty($files)): ?>
    <div class="empty-state fade-in">
        <div class="icon">📄</div>
        <h3>No Notes Yet</h3>
        <p>Upload notes from the admin panel.</p>
        <a href="index.php" class="neu-btn" style="padding:10px 22px;margin-top:20px">← Back to Subjects</a>
    </div>
    <?php else: ?>
    <div class="files-grid">
        <?php foreach ($files as $file): ?>
        <div class="file-card-wrap">
            <div class="neu-card file-card <?= $file['is_locked'] ? 'is-locked' : '' ?> fade-in">

                <!-- Lock Status Badge (top-right) -->
                <div style="position:absolute;top:14px;right:14px">
                    <?php if ($file['is_locked']): ?>
                        <span class="lock-badge locked">🔒 Locked</span>
                    <?php else: ?>
                        <span class="lock-badge unlocked">🔓 Available</span>
                    <?php endif; ?>
                </div>

                <!-- File Header -->
                <div class="file-header" style="padding-right:90px">
                    <div class="file-icon-wrap">
                        <?php if ($file['is_locked']): ?>
                            🔒
                        <?php elseif ($file['file_type'] === 'link'): ?>
                            🔗
                        <?php else: ?>
                            📄
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <div class="file-title"><?= e($file['title']) ?></div>
                    </div>
                </div>

                <!-- Description -->
                <?php if ($file['description']): ?>
                <div class="file-desc-wrap">
                    <p class="file-desc"><?= e($file['description']) ?></p>
                    <a class="description-toggle" href="description.php?id=<?= (int)$file['id'] ?>">See more</a>
                </div>
                <?php endif; ?>

                <?php if (!$file['is_locked'] && $file['external_link']): ?>
                <a class="file-external-link" href="<?= e($file['external_link']) ?>" target="_blank" rel="noopener noreferrer">
                    🔗 <span><?= e($file['external_link']) ?></span>
                </a>
                <?php endif; ?>

                <!-- Meta -->
                <div class="file-meta">
                    <span class="meta-badge">
                        <?php
                        if ($file['file_type'] === 'pdf')       echo '📄 PDF';
                        elseif ($file['file_type'] === 'link')  echo '🔗 Link';
                        else                                    echo '📄 PDF + 🔗 Link';
                        ?>
                    </span>
                    <?php if ($file['file_size']): ?>
                    <span class="meta-badge">📦 <?= e($file['file_size']) ?></span>
                    <?php endif; ?>
                    <?php if (!$file['is_locked']): ?>
                    <span class="meta-badge">⬇️ <?= $file['downloads'] ?> downloads</span>
                    <?php endif; ?>
                </div>

                <!-- Actions or Locked Message -->
                <?php if ($file['is_locked']): ?>
                <div class="locked-overlay">
                    <div class="lock-icon">🔒</div>
                    <p>This note is currently locked.<br>Contact admin to get access.</p>
                </div>

                <?php else:
                    // Determine view URL
                    $viewURL = '';
                    if ($file['file_path'])     $viewURL = SITE_URL . '/uploads/' . $file['file_path'];
                    elseif ($file['external_link']) $viewURL = $file['external_link'];
                ?>
                <div class="file-actions <?= ($file['file_path'] && $file['external_link']) ? 'has-both' : '' ?>">
                    <?php if ($file['file_path'] && $viewURL): ?>
                    <button class="btn-view"
                        onclick="openPDF('<?= e($viewURL) ?>', '<?= e(addslashes($file['title'])) ?>')">
                        👁️ View
                    </button>
                    <?php endif; ?>
                    <?php if ($file['external_link']): ?>
                    <a class="btn-view" href="<?= e($file['external_link']) ?>" target="_blank" rel="noopener noreferrer">
                        🔗 Open Link
                    </a>
                    <?php endif; ?>
                    <?php if ($file['file_path']): ?>
                    <a href="folder.php?id=<?= $folder_id ?>&download=<?= $file['id'] ?>" class="btn-download">
                        ⬇️ Download
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="padding-bottom:40px">
        <a href="index.php" class="neu-btn" style="padding:10px 22px">← Back to Subjects</a>
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
