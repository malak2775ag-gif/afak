<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Announcements';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId = (int)$_POST['announcement_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        // Approve an announcement (from draft or rejected state)
        // This action will also publish the announcement by setting published_at
        $stmt = $pdo->prepare("UPDATE announcements SET status = 'approved', approved_by = ?, approved_at = NOW(), published_at = NOW() WHERE id = ? AND status IN ('draft', 'rejected')");
        $stmt->execute([$_SESSION['user_id'], $annId]);

        $stmt = $pdo->prepare("SELECT created_by, title FROM announcements WHERE id = ?");
        $stmt->execute([$annId]);
        $ann = $stmt->fetch();
        if ($ann) {
            createNotification(
                $pdo,
                (int)$ann['created_by'],
                'announcement',
                'Announcement Approved',
                'Your announcement "' . $ann['title'] . '" was approved and published.',
                'announcement',
                $annId,
                url('instructor/announcements.php') // Assuming this page exists for instructors to view their announcements
            );
        }
        flash('success', 'Announcement approved.');
    } elseif ($action === 'reject') {
        // Reject an announcement (from draft or approved state)
        // Rejection also unpublishes the announcement by setting published_at to NULL
        $stmt = $pdo->prepare("UPDATE announcements SET status = 'rejected', approved_by = ?, approved_at = NOW(), published_at = NULL WHERE id = ? AND status IN ('draft', 'approved')");
        $stmt->execute([$_SESSION['user_id'], $annId]);

        $stmt = $pdo->prepare("SELECT created_by, title FROM announcements WHERE id = ?");
        $stmt->execute([$annId]);
        $ann = $stmt->fetch();
        if ($ann) {
            createNotification(
                $pdo,
                (int)$ann['created_by'],
                'announcement',
                'Announcement Rejected',
                'Your announcement "' . $ann['title'] . '" was rejected by admin.',
                'announcement',
                $annId,
                url('instructor/announcements.php') // Assuming this page exists for instructors to view their announcements
            );
        }
        flash('success', 'Announcement rejected.');
    } elseif ($action === 'delete') {
        // Delete any announcement
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$annId]);
        flash('success', 'Announcement deleted.');
    }
    header('Location: ' . url('admin/announcements.php'));
    exit;
}

// Fetch all announcements, regardless of status, for admin to manage
$announcements = $pdo->query("
    SELECT a.*, c.title as course_title, u.first_name, u.last_name
    FROM announcements a
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Announcements</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Title</th>
            <th style="padding: 1rem;">Course</th>
            <th style="padding: 1rem;">Content Preview</th>
            <th style="padding: 1rem;">Author</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($announcements as $a): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;"><?= e($a['title']) ?></td>
            <td style="padding: 1rem;"><?= e($a['course_title']) ?></td>
            <td style="padding: 1rem;">
                <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" 
                        onclick="togglePreview(<?= $a['id'] ?>)">View Content</button>
                <div id="preview-<?= $a['id'] ?>" style="display:none; margin-top:10px; padding:10px; background:#f9f9f9; border:1px solid #ddd; border-radius:8px; max-width:300px;">
                    <?= nl2br(e($a['content'])) ?>
                    <?php 
                    if (strpos($a['content'], 'h5p') !== false): 
                        $url = trim($a['content']);
                    ?>
                        <div style="margin-top:10px;">
                            <iframe src="<?= e($url) ?>" width="100%" height="200" frameborder="0" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td style="padding: 1rem;"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></td>
            <td style="padding: 1rem;"><?= e($a['status']) ?></td>
            <td style="padding: 1rem;">
                <?php if ($a['status'] === 'draft' || $a['status'] === 'rejected'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Approve</button>
                </form>
                <?php endif; ?>
                <?php if ($a['status'] === 'draft' || $a['status'] === 'approved'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="action" value="reject" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Reject</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="action" value="delete" class="btn" style="background: #6c757d; color: white; padding: 0.35rem 0.75rem;">Delete</button>
                </form>
                <?php if ($a['status'] === 'approved' && empty($a['published_at'])): ?>
                    <!-- Optionally add a 'Publish' button here if 'approved' is distinct from 'published' and needs a separate action -->
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php if (empty($announcements)): ?>
    <p class="text-muted">No announcements.</p>
<?php endif ?>

<script>
function togglePreview(id) {
    const div = document.getElementById('preview-' + id);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
