<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Categories';
$flash = getFlash();
$errors = [];
$formValues = ['name' => '', 'slug' => '', 'description' => '', 'parent_id' => ''];

// Check if in edit mode
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $categoryToEdit = $stmt->fetch();
    if ($categoryToEdit) {
        $formValues = $categoryToEdit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $slug = trim($slug, '-');
        $description = trim($_POST['description'] ?? '');
        $parentId = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        $formValues = ['name' => $name, 'slug' => $slug, 'description' => $description, 'parent_id' => $parentId];

        if (!$name) {
            $errors[] = "Category name is required.";
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Invalid slug format. Use only lowercase letters, numbers, and dashes.";
        } else {
            // Check if the slug is not used by another category
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1");
            $stmt->execute([$slug, $editId]);
            if ($stmt->fetch()) {
                $errors[] = "The slug '$slug' is already taken. Please provide a unique one.";
            }
        }

        // Prevent making a category a sub-category of itself
        if ($editId && $parentId === $editId) {
            $errors[] = "A category cannot be its own parent.";
        }

        if (empty($errors)) {
            if ($editId) {
                $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ? WHERE id = ?")
                    ->execute([$name, $slug, $description, $parentId, $editId]);
                flash('success', 'Category updated successfully.');
            } else {
                $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)")
                    ->execute([$name, $slug, $description, $parentId]);
                flash('success', 'Category added successfully.');
            }
            header('Location: ' . url('admin/categories.php'));
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        flash('success', 'Category deleted.');
        header('Location: ' . url('admin/categories.php'));
        exit;
    }
}

$categories = getHierarchicalCategories($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Categories</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<div class="form-card" style="max-width: 500px; margin-bottom: 2rem; border-top: 4px solid var(--primary);">
    <h3><?= $editId ? 'Edit Category' : 'Add New Category' ?></h3>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="padding: 10px; font-size: 0.9rem; margin-bottom: 15px;">
            <?php foreach ($errors as $err): ?>
                <div>• <?= e($err) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form method="POST">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($formValues['name']) ?>" placeholder="e.g. Graphic Design" required>
        </div>
        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" class="form-control" value="<?= e($formValues['slug']) ?>" placeholder="URL identifier (auto-generated if empty)">
        </div>
        <div class="form-group">
            <label>Parent Category (Optional)</label>
            <select name="parent_id" class="form-control">
                <option value="">Main Category (None)</option>
                <?php foreach ($categories as $cat): ?>
                    <?php if ($editId && $cat['id'] == $editId) continue; ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (string)($formValues['parent_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['display_name']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="2"><?= e($formValues['description']) ?></textarea>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="save" class="btn btn-primary"><?= $editId ? 'Update' : 'Add' ?></button>
            <?php if ($editId): ?>
                <a href="<?= url('admin/categories.php') ?>" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left; width: 40%;">Name</th>
            <th style="padding: 1rem;">Slug</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;">
                <span style="<?= $cat['parent_id'] ? 'color: #666;' : 'font-weight: bold; color: var(--primary);' ?>">
                    <?= e($cat['display_name']) ?>
                </span>
            </td>
            <td style="padding: 1rem; font-family: monospace; font-size: 0.85rem; color: #777;"><?= e($cat['slug']) ?></td>
            <td style="padding: 1rem;">
                <a href="<?= url('admin/categories.php?edit=' . $cat['id']) ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Edit</a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete?');">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" name="delete" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
