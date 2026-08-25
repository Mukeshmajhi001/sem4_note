<?php
// admin/folders.php — Subject Folder CRUD
require_once '../config.php';
requireAdmin();

$message  = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['name']        ?? '');
        $icon  = trim($_POST['icon']        ?? '📁');
        $color = trim($_POST['color']       ?? '#667eea');
        $desc  = trim($_POST['description'] ?? '');
        if ($name) {
            $pdo->prepare("INSERT INTO folders (name,icon,color,description) VALUES(?,?,?,?)")
                ->execute([$name, $icon, $color, $desc]);
            $message = "✅ Subject folder \"{$name}\" created successfully!";
        } else { $message = '❌ Folder name is required.'; $msg_type = 'error'; }

    } elseif ($action === 'edit') {
        $id   = (int)($_POST['id']   ?? 0);
        $name = trim($_POST['name']  ?? '');
        $icon = trim($_POST['icon']  ?? '📁');
        $color= trim($_POST['color'] ?? '#667eea');
        $desc = trim($_POST['description'] ?? '');
        if ($id && $name) {
            $pdo->prepare("UPDATE folders SET name=?,icon=?,color=?,description=? WHERE id=?")
                ->execute([$name,$icon,$color,$desc,$id]);
            $message = "✅ Folder updated successfully!";
        } else { $message = '❌ Invalid data.'; $msg_type = 'error'; }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Delete uploaded files from disk
            $rows = $pdo->prepare("SELECT file_path FROM files WHERE folder_id=?");
            $rows->execute([$id]);
            foreach ($rows->fetchAll() as $r) {
                if ($r['file_path'] && file_exists(UPLOAD_DIR . $r['file_path']))
                    unlink(UPLOAD_DIR . $r['file_path']);
            }
            $pdo->prepare("DELETE FROM folders WHERE id=?")->execute([$id]);
            $message = "🗑️ Folder and all its notes deleted.";
        }
    }
}

// Edit mode
$editFolder = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM folders WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editFolder = $s->fetch();
}

$folders = $pdo->query("
    SELECT f.*, COUNT(fi.id) AS file_count
    FROM folders f LEFT JOIN files fi ON fi.folder_id=f.id
    GROUP BY f.id ORDER BY f.name
")->fetchAll();

$colors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#f7971e','#0ba360','#c471f5','#ee0979','#30cfd0'];
$icons  = ['📁','📚','📐','⚛️','🧪','💻','🧬','📖','🎓','🔬','📊','🏛️','✏️','🗂️','📝','🌍','⚗️','🧮'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects — <?= e(SITE_NAME) ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">

        <div class="admin-header">
            <h1>📂 Subjects / Folders</h1>
            <button class="neu-btn theme-toggle" id="themeToggle"
                style="width:44px;height:44px;border-radius:50%;font-size:1.2rem">🌙</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <!-- Create / Edit Form -->
        <div class="neu-card form-section fade-in">
            <h2><?= $editFolder ? '✏️ Edit Folder' : '➕ Create New Subject Folder' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editFolder ? 'edit' : 'create' ?>">
                <?php if ($editFolder): ?>
                <input type="hidden" name="id" value="<?= $editFolder['id'] ?>">
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-row">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="name" class="neu-input"
                            placeholder="e.g. Mathematics"
                            value="<?= e($editFolder['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label">Accent Color</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:4px">
                            <?php foreach ($colors as $c): ?>
                            <label>
                                <input type="radio" name="color" value="<?= $c ?>"
                                    <?= ($editFolder['color'] ?? '#667eea') === $c ? 'checked' : '' ?>
                                    style="display:none">
                                <span style="display:block;width:28px;height:28px;border-radius:50%;
                                    background:<?= $c ?>;cursor:pointer;
                                    box-shadow:0 2px 8px <?= $c ?>66;
                                    outline:<?= ($editFolder['color'] ?? '#667eea') === $c ? '3px solid var(--text)' : 'none' ?>;
                                    outline-offset:2px;transition:all .2s">
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label">Icon (select or type emoji)</label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">
                        <?php foreach ($icons as $ic): ?>
                        <label>
                            <input type="radio" name="icon_pick" value="<?= $ic ?>" style="display:none"
                                onclick="document.getElementById('iconInput').value=this.value">
                            <span style="display:inline-block;font-size:1.6rem;padding:4px;
                                border-radius:8px;cursor:pointer;border:2px solid transparent;
                                transition:all .2s" title="<?= $ic ?>"
                                onmouseover="this.style.background='var(--input-bg)'"
                                onmouseout="this.style.background=''"
                                onclick="this.style.border='2px solid var(--accent)'">
                                <?= $ic ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="icon" id="iconInput" class="neu-input"
                        style="width:140px" placeholder="Emoji"
                        value="<?= e($editFolder['icon'] ?? '📁') ?>">
                </div>

                <div class="form-row">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" class="form-textarea"
                        placeholder="Brief description of this subject..."><?= e($editFolder['description'] ?? '') ?></textarea>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <button type="submit" class="btn-primary">
                        <?= $editFolder ? '💾 Save Changes' : '✅ Create Folder' ?>
                    </button>
                    <?php if ($editFolder): ?>
                    <a href="folders.php" class="neu-btn" style="padding:13px 24px;font-weight:700">
                        ✕ Cancel
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Folders Table -->
        <div class="neu-card form-section fade-in fade-in-1">
            <h2>📋 All Subject Folders (<?= count($folders) ?>)</h2>
            <?php if (empty($folders)): ?>
            <p style="color:var(--text-muted)">No folders yet. Create one above.</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Icon</th><th>Name</th><th>Color</th><th>Notes</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($folders as $f): ?>
                <tr>
                    <td data-label="Icon" style="font-size:1.5rem"><?= e($f['icon']) ?></td>
                    <td data-label="Name" style="font-weight:700">
                        <?= e($f['name']) ?>
                        <?php if ($f['description']): ?>
                        <br><span style="font-size:.78rem;color:var(--text-muted);font-weight:400">
                            <?= e(mb_substr($f['description'],0,60)) ?>…
                        </span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Color">
                        <span style="display:inline-block;width:20px;height:20px;border-radius:50%;
                            background:<?= e($f['color']) ?>;vertical-align:middle;margin-right:6px;
                            box-shadow:0 2px 6px <?= e($f['color']) ?>88"></span>
                        <code style="font-size:.8rem"><?= e($f['color']) ?></code>
                    </td>
                    <td data-label="Notes"><span class="meta-badge"><?= $f['file_count'] ?> file(s)</span></td>
                    <td data-label="Actions">
                        <div class="table-actions">
                            <a href="folders.php?edit=<?= $f['id'] ?>" class="btn-edit">✏️ Edit</a>
                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('Delete \"<?= e(addslashes($f['name'])) ?>\" and ALL its notes? This cannot be undone.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= $f['id'] ?>">
                                <button type="submit" class="btn-danger">🗑️ Delete</button>
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
