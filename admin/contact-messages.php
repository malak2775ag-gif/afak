<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Contact Messages';
$flash = getFlash();

$tableMissing = false;
$rows = [];

try {
    $rows = $pdo->query('SELECT name, email, number, comment FROM contact_us LIMIT 200')->fetchAll();
} catch (PDOException $e) {
    $tableMissing = true;
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Contact Messages</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<?php if ($tableMissing): ?>
    <div class="alert alert-danger">The <code>contact_us</code> table is missing. Import <code>database/schema.sql</code> (or add the contact_us section) and refresh.</div>
<?php elseif (empty($rows)): ?>
    <p class="text-muted">No messages yet.</p>
<?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.5rem;">Name</th>
                    <th style="padding: 0.5rem;">Email</th>
                    <th style="padding: 0.5rem;">Number</th>
                    <th style="padding: 0.5rem;">Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr style="border-bottom: 1px solid #eee; vertical-align: top;">
                        <td style="padding: 0.5rem;"><?= e($r['name']) ?></td>
                        <td style="padding: 0.5rem;"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td>
                        <td style="padding: 0.5rem;"><?= e((string) ($r['number'] ?? '')) ?></td>
                        <td style="padding: 0.5rem; max-width: 320px;"><?= nl2br(e($r['comment'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<p><a href="<?= url('admin/index.php') ?>">← Admin Dashboard</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
