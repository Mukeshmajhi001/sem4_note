<?php
// admin/files.php — Notes / Files Management (Upload + Lock/Unlock)
require_once '../config.php';
requireAdmin();

$message  = '';
$msg_type = 'success';

// ── TOGGLE LOCK ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_lock') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $curr = $pdo->prepare("SELECT is_locked FROM files WHERE id=?");
        $curr->execute([$id]);
        $row = $curr->fetch();
        if ($row) {
            $newVal = $row['is_locked'] ? 0 : 1;
            $pdo->prepare("UPDATE files SET is_locked=? WHERE id=?")->execute([$newVal, $id]);
            $message = $newVal
                ? "🔒 Note locked — students can no longer view or download it."
                : "🔓 Note unlocked — students can now view and download it.";
        }
    }
}

// ── CREATE ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $folder_id   = (int)($_POST['folder_id']   ?? 0);
    $title       = trim($_POST['title']         ?? '');
    $description = trim($_POST['description']   ?? '');
    $file_type   = $_POST['file_type']           ?? 'pdf';
    $ext_link    = trim($_POST['external_link'] ?? '');
    $is_locked   = isset($_POST['is_locked']) ? 1 : 0;

    if (!in_array($file_type, ['pdf', 'link', 'both'], true)) {
        $message = '❌ Invalid upload type.'; $msg_type = 'error';
    } elseif (!$folder_id || !$title) {
        $message = '❌ Subject folder and title are required.'; $msg_type = 'error';
    } else {
        $file_path = null; $file_size = null; $ok = true;

        // PDF Upload
        if (($file_type === 'pdf' || $file_type === 'both')
            && isset($_FILES['pdf_file'])
            && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {

            $tmp  = $_FILES['pdf_file']['tmp_name'];
            $name = $_FILES['pdf_file']['name'];
            $size = $_FILES['pdf_file']['size'];
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp);

            if (!in_array($mime, ['application/pdf','application/x-pdf'])) {
                $message = '❌ Only PDF files are allowed.'; $msg_type='error'; $ok=false;
            } elseif ($size > MAX_FILE_SIZE) {
                $message = '❌ File too large. Maximum allowed size is 50 MB.'; $msg_type='error'; $ok=false;
            } else {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                $unique = uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/','_',$name);
                if (move_uploaded_file($tmp, UPLOAD_DIR.$unique)) {
                    $file_path = $unique;
                    $file_size = formatFileSize($size);
                } else {
                    $message = '❌ Upload failed. Check folder write permissions.'; $msg_type='error'; $ok=false;
                }
            }
        } elseif ($file_type === 'pdf' && (empty($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK)) {
            $message = '❌ Please select a PDF file to upload.'; $msg_type='error'; $ok=false;
        }

        // Validate external link and make sure each selected type has its content.
        if ($ok && ($file_type === 'link' || $file_type === 'both')) {
            if (!$ext_link) {
                $message = '❌ Please enter an external link.'; $msg_type='error'; $ok=false;
            } elseif (!filter_var($ext_link, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($ext_link, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $message = '❌ Please enter a valid http:// or https:// URL.'; $msg_type='error'; $ok=false;
            }
        }

        if ($ok && $file_type === 'both' && !$file_path) {
            $message = '❌ Please select a PDF file for PDF + Link type.'; $msg_type='error'; $ok=false;
        }

        if ($ok) {
            $pdo->prepare("INSERT INTO files
                (folder_id,title,description,file_path,external_link,file_type,file_size,is_locked)
                VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$folder_id,$title,$description,$file_path,$ext_link?:null,$file_type,$file_size,$is_locked]);
            $message = "✅ \"{$title}\" uploaded successfully!"
                . ($is_locked ? " (Note is currently locked 🔒)" : " (Note is accessible 🔓)");
        }
    }
}

// ── DELETE ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $r = $pdo->prepare("SELECT file_path FROM files WHERE id=?");
        $r->execute([$id]);
        $row = $r->fetch();
        if ($row && $row['file_path'] && file_exists(UPLOAD_DIR.$row['file_path']))
            unlink(UPLOAD_DIR.$row['file_path']);
        $pdo->prepare("DELETE FROM files WHERE id=?")->execute([$id]);
        $message = "🗑️ Note deleted successfully.";
    }
}

// ── FILTERS ──────────────────────────────────
$filter_folder = isset($_GET['folder']) ? (int)$_GET['folder'] : 0;
$filter_locked = isset($_GET['locked']) ? (int)$_GET['locked'] : -1; // -1=all, 0=unlocked, 1=locked

$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();

// Build query with filters
$where = [];
$params = [];
if ($filter_folder) { $where[] = 'f.folder_id=?'; $params[] = $filter_folder; }
if ($filter_locked >= 0) { $where[] = 'f.is_locked=?'; $params[] = $filter_locked; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT f.*, fo.name AS folder_name
    FROM files f JOIN folders fo ON fo.id=f.folder_id
    $whereSQL ORDER BY f.created_at DESC
");
$stmt->execute($params);
$files = $stmt->fetchAll();

$lockCount   = $pdo->query("SELECT COUNT(*) FROM files WHERE is_locked=1")->fetchColumn();
$unlockCount = $pdo->query("SELECT COUNT(*) FROM files WHERE is_locked=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes / Files — <?= e(SITE_NAME) ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">

        <div class="admin-header">
            <h1>📄 Notes / Files</h1>
            <button class="neu-btn theme-toggle" id="themeToggle"
                style="width:44px;height:44px;border-radius:50%;font-size:1.2rem">🌙</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (empty($folders)): ?>
        <div class="alert alert-error">
            ⚠️ No subject folders found.
            <a href="folders.php" style="color:inherit;text-decoration:underline">Create a folder first</a>
            before uploading notes.
        </div>
        <?php else: ?>

        <!-- ── UPLOAD FORM ── -->
        <div class="neu-card form-section fade-in">
            <h2>📤 Upload New Note</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-row">
                        <label class="form-label">Subject Folder *</label>
                        <select name="folder_id" class="form-select" required>
                            <option value="">— Select subject —</option>
                            <?php foreach ($folders as $fo): ?>
                            <option value="<?= $fo['id'] ?>"><?= e($fo['icon'].' '.$fo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label class="form-label">Note Title *</label>
                        <input type="text" name="title" class="neu-input"
                            placeholder="e.g. Calculus Chapter 1" required>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" class="form-textarea"
                        placeholder="Brief description of this note..."></textarea>
                </div>

                <!-- Upload Type -->
                <div class="form-row">
                    <label class="form-label">Upload Type *</label>
                    <div class="type-selector">
                        <input type="radio" name="file_type" id="t_pdf"  value="pdf"  class="type-option" checked>
                        <label for="t_pdf"  class="type-label">📄 PDF Only</label>
                        <input type="radio" name="file_type" id="t_link" value="link" class="type-option">
                        <label for="t_link" class="type-label">🔗 Link Only</label>
                        <input type="radio" name="file_type" id="t_both" value="both" class="type-option">
                        <label for="t_both" class="type-label">📄 + 🔗 Both</label>
                    </div>
                </div>

                <!-- PDF Section -->
                <div id="pdfSection" class="form-row">
                    <label class="form-label">PDF File</label>
                    <div class="file-upload-area">
                        <input type="file" name="pdf_file" accept=".pdf,application/pdf">
                        <div class="file-upload-icon">📄</div>
                        <div class="file-upload-text">
                            Drop PDF here or click to browse<br>
                            <small style="color:var(--text-muted)">Max 50 MB · PDF only</small>
                        </div>
                    </div>
                </div>

                <!-- Link Section -->
                <div id="linkSection" class="form-row" style="display:none">
                    <label class="form-label">External Link (Google Drive, Dropbox, etc.)</label>
                    <input type="url" name="external_link" class="neu-input"
                        placeholder="https://drive.google.com/file/d/...">
                    <small style="color:var(--text-muted);margin-top:6px;display:block">
                        💡 For Google Drive: File → Share → "Anyone with the link" → Copy link
                    </small>
                </div>

                <!-- Lock Option -->
                <div class="form-row" style="margin-top:4px">
                    <label class="form-label">Access Control</label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;
                            padding:12px 18px;border-radius:var(--radius-sm);box-shadow:var(--neu-raised-sm);">
                            <input type="checkbox" name="is_locked" id="lockCheck"
                                style="width:18px;height:18px;accent-color:var(--danger);cursor:pointer">
                            <span>
                                <strong>🔒 Upload as Locked</strong><br>
                                <small style="color:var(--text-muted)">Students will see a "Locked" badge — cannot view or download.</small>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top:8px">
                    ✅ Upload Note
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- ── FILES TABLE ── -->
        <div class="neu-card form-section fade-in fade-in-1">
            <h2 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <span>
                    📋 All Notes
                    <span class="lock-badge unlocked" style="margin-left:8px;font-size:.75rem"><?= $unlockCount ?> open</span>
                    <span class="lock-badge locked"   style="margin-left:4px;font-size:.75rem"><?= $lockCount ?> locked</span>
                </span>
                <!-- Filters -->
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <select name="folder" class="form-select" style="width:auto;min-width:150px"
                            onchange="this.form.submit()">
                            <option value="">All Subjects</option>
                            <?php foreach ($folders as $fo): ?>
                            <option value="<?= $fo['id'] ?>" <?= $filter_folder==$fo['id']?'selected':'' ?>>
                                <?= e($fo['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="locked" class="form-select" style="width:auto"
                            onchange="this.form.submit()">
                            <option value="-1" <?= $filter_locked===-1?'selected':'' ?>>All Status</option>
                            <option value="0"  <?= $filter_locked===0 ?'selected':'' ?>>🔓 Accessible</option>
                            <option value="1"  <?= $filter_locked===1 ?'selected':'' ?>>🔒 Locked</option>
                        </select>
                        <?php if ($filter_folder || $filter_locked >= 0): ?>
                        <a href="files.php" class="neu-btn" style="padding:10px 14px;font-size:.85rem">✕ Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </h2>

            <?php if (empty($files)): ?>
            <p style="color:var(--text-muted)">No notes found for the selected filters.</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Downloads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $f): ?>
                <tr>
                    <td data-label="Title" style="font-weight:700;max-width:200px">
                        <?= e($f['title']) ?>
                        <?php if ($f['description']): ?>
                        <br><span style="font-size:.78rem;color:var(--text-muted);font-weight:400">
                            <?= e(mb_substr($f['description'],0,55)) ?>…
                        </span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Subject"><?= e($f['folder_name']) ?></td>
                    <td data-label="Type">
                        <span class="meta-badge">
                            <?= $f['file_type']==='pdf' ? '📄 PDF'
                                : ($f['file_type']==='link' ? '🔗 Link' : '📄+🔗') ?>
                        </span>
                        <?php if ($f['file_size']): ?>
                        <br><span style="font-size:.75rem;color:var(--text-muted)"><?= e($f['file_size']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <?php if ($f['is_locked']): ?>
                        <span class="lock-badge locked">🔒 Locked</span>
                        <?php else: ?>
                        <span class="lock-badge unlocked">🔓 Available</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Downloads">
                        <?= $f['is_locked'] ? '<span style="color:var(--text-muted)">—</span>' : '⬇️ '.$f['downloads'] ?>
                    </td>
                    <td data-label="Actions">
                        <div class="table-actions">
                            <!-- Lock / Unlock Toggle -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_lock">
                                <input type="hidden" name="id"     value="<?= $f['id'] ?>">
                                <?php if ($f['is_locked']): ?>
                                <button type="submit" class="btn-lock-toggle unlock"
                                    title="Click to unlock — students will be able to access this note">
                                    🔓 Unlock
                                </button>
                                <?php else: ?>
                                <button type="submit" class="btn-lock-toggle lock"
                                    title="Click to lock — students will not be able to view or download">
                                    🔒 Lock
                                </button>
                                <?php endif; ?>
                            </form>

                            <!-- Preview -->
                            <?php
                            $purl = $f['file_path'] ? '../uploads/'.$f['file_path'] : $f['external_link'];
                            ?>
                            <?php if ($purl): ?>
                            <a href="<?= e($purl) ?>" target="_blank"
                               class="btn-edit" style="background:linear-gradient(135deg,#4facfe,#00f2fe)">
                                👁️ View
                            </a>
                            <?php endif; ?>

                            <!-- Delete -->
                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('Delete \"<?= e(addslashes($f['title'])) ?>\"? This cannot be undone.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= $f['id'] ?>">
                                <button type="submit" class="btn-danger">🗑️</button>
                            </form>
                        </div>
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
