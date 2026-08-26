<?php
// admin/edit.php — Edit an existing note
require_once '../config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM files WHERE id=?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    header('Location: files');
    exit;
}

$message = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder_id   = (int)($_POST['folder_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file_type   = $_POST['file_type'] ?? 'pdf';
    $ext_link    = trim($_POST['external_link'] ?? '');
    $is_locked   = isset($_POST['is_locked']) ? 1 : 0;

    if (!$folder_id || !$title || !in_array($file_type, ['pdf', 'link', 'both'], true)) {
        $message = '❌ Please provide valid note details.'; $msg_type = 'error';
    } elseif (($file_type === 'link' || $file_type === 'both') &&
        (!$ext_link || !filter_var($ext_link, FILTER_VALIDATE_URL) ||
         !in_array(strtolower((string)parse_url($ext_link, PHP_URL_SCHEME)), ['http', 'https'], true))) {
        $message = '❌ Please enter a valid http:// or https:// URL.'; $msg_type = 'error';
    } elseif (($file_type === 'pdf' || $file_type === 'both') && !$note['file_path'] &&
        (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK)) {
        $message = '❌ This note needs a PDF file.'; $msg_type = 'error';
    } else {
        $newPath = $note['file_path'];
        $newSize = $note['file_size'];
        $uploadedNewFile = false;
        $ok = true;

        if (($file_type === 'pdf' || $file_type === 'both') && isset($_FILES['pdf_file']) &&
            $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['pdf_file']['tmp_name'];
            $size = $_FILES['pdf_file']['size'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);

            if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                $message = '❌ Only PDF files are allowed.'; $msg_type = 'error'; $ok = false;
            } elseif ($size > MAX_FILE_SIZE) {
                $message = '❌ File too large. Maximum allowed size is 50 MB.'; $msg_type = 'error'; $ok = false;
            } else {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['pdf_file']['name']);
                $newPath = uniqid() . '_' . $safeName;
                if (move_uploaded_file($tmp, UPLOAD_DIR . $newPath)) {
                    $newSize = formatFileSize($size);
                    $uploadedNewFile = true;
                } else {
                    $message = '❌ Could not replace the PDF. Check folder write permissions.'; $msg_type = 'error'; $ok = false;
                }
            }
        }

        if ($ok && $file_type === 'link') {
            $newPath = null; $newSize = null;
        } elseif ($ok && $file_type === 'both' && !$newPath) {
            $message = '❌ Please select a PDF file for PDF + Link type.'; $msg_type = 'error'; $ok = false;
        }

        if ($ok) {
            $pdo->prepare("UPDATE files SET folder_id=?,title=?,description=?,file_path=?,external_link=?,file_type=?,file_size=?,is_locked=? WHERE id=?")
                ->execute([$folder_id, $title, $description, $newPath, $ext_link ?: null, $file_type, $newSize, $is_locked, $id]);
            if ($uploadedNewFile && $note['file_path'] && file_exists(UPLOAD_DIR . $note['file_path'])) {
                unlink(UPLOAD_DIR . $note['file_path']);
            }
            header('Location: files?updated=1');
            exit;
        }

        if ($uploadedNewFile && file_exists(UPLOAD_DIR . $newPath)) {
            unlink(UPLOAD_DIR . $newPath);
        }
    }
}

$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note — <?= e(SITE_NAME) ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-header">
            <h1>✏️ Edit Note</h1>
            <button class="neu-btn theme-toggle" id="themeToggle" style="width:44px;height:44px;border-radius:50%;font-size:1.2rem">🌙</button>
        </div>

        <?php if ($message): ?><div class="alert alert-<?= $msg_type ?>"><?= e($message) ?></div><?php endif; ?>

        <div class="neu-card form-section fade-in">
            <h2><?= e($note['title']) ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $note['id'] ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-row">
                        <label class="form-label">Subject Folder *</label>
                        <select name="folder_id" class="form-select" required>
                            <?php foreach ($folders as $folder): ?>
                            <option value="<?= $folder['id'] ?>" <?= (int)$note['folder_id'] === (int)$folder['id'] ? 'selected' : '' ?>><?= e($folder['icon'].' '.$folder['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label class="form-label">Note Title *</label>
                        <input type="text" name="title" class="neu-input" value="<?= e($note['title']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" class="form-textarea"><?= e($note['description']) ?></textarea>
                </div>
                <div class="form-row">
                    <label class="form-label">Note Type *</label>
                    <div class="type-selector">
                        <?php foreach (['pdf'=>'📄 PDF Only','link'=>'🔗 Link Only','both'=>'📄 + 🔗 Both'] as $type => $label): ?>
                        <input type="radio" name="file_type" id="edit_type_<?= $type ?>" value="<?= $type ?>" class="edit-type-option" <?= $note['file_type'] === $type ? 'checked' : '' ?>>
                        <label for="edit_type_<?= $type ?>" class="type-label"><?= $label ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-row">
                    <label class="form-label">Replace PDF (optional)</label>
                    <input type="file" name="pdf_file" accept=".pdf,application/pdf" class="neu-input">
                    <?php if ($note['file_path']): ?><small style="color:var(--text-muted);display:block;margin-top:6px">Current file: <?= e($note['file_path']) ?> · Leave empty to keep it.</small><?php endif; ?>
                </div>
                <div class="form-row">
                    <label class="form-label">External Link</label>
                    <input type="url" name="external_link" class="neu-input" value="<?= e($note['external_link']) ?>" placeholder="https://...">
                </div>
                <div class="form-row">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" name="is_locked" style="width:18px;height:18px;accent-color:var(--danger)" <?= $note['is_locked'] ? 'checked' : '' ?>>
                        <strong>🔒 Keep this note locked</strong>
                    </label>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <button type="submit" class="btn-primary">💾 Save Changes</button>
                    <a href="files" class="neu-btn" style="padding:13px 24px;font-weight:700">✕ Cancel</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
