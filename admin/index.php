<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Admin Dashboard';
$user = getCurrentUser();

// Stats
$contactCount = 0;
try {
    $contactCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_us')->fetchColumn();
} catch (PDOException $e) {
    $contactCount = 0;
}

$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'enrollments' => $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn(),
    'pending_review' => $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'pending_review'")->fetchColumn(),
    'pending_certs' => $pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'pending_admin'")->fetchColumn(),
    'pending_payments' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
    'contact_messages' => $contactCount,
];

require_once __DIR__ . '/../includes/header.php';
?>

<?php $avatar = ($user && !empty($user['avatar_url'])) ? $user['avatar_url'] : 'assets/img/admin.png'; ?>
<div class="dashboard-header">
    <div class="user-welcome">
        <img src="<?= url($avatar) ?>" alt="Avatar" class="user-avatar-large">
        <h2 class="section-title">Admin Dashboard</h2>
    </div>
    <a href="<?= url('profile.php') ?>" title="Profile Settings" class="settings-link">
        <img src="<?= url('assets/img/setting.jpg') ?>" alt="Settings" class="settings-icon">
    </a>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="number"><?= $stats['users'] ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['courses'] ?></div>
        <div class="label">Courses</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['enrollments'] ?></div>
        <div class="label">Enrollments</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['pending_review'] ?></div>
        <div class="label"><a href="<?= url('admin/courses.php?status=pending_review') ?>">Pending Review</a></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['pending_certs'] ?></div>
        <div class="label"><a href="<?= url('admin/certificates.php') ?>">Pending Certificates</a></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['pending_payments'] ?></div>
        <div class="label"><a href="<?= url('admin/payments.php') ?>">Pending Payments</a></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['contact_messages'] ?></div>
        <div class="label"><a href="<?= url('admin/contact-messages.php') ?>">Contact Messages</a></div>
    </div>
</div>

<div class="dashboard-grid">
    <aside class="sidebar">
        <h3 style="margin-top: 0;">Admin Menu</h3>
        <nav class="sidebar-nav">
            <a href="<?= url('admin/index.php') ?>" class="active">Dashboard</a>
            <a href="<?= url('admin/users.php') ?>">Manage Users</a>
            <a href="<?= url('admin/courses.php') ?>">Manage Courses</a>
            <a href="<?= url('admin/categories.php') ?>">Categories</a>
            <a href="<?= url('admin/announcements.php') ?>">Announcements</a>
            <a href="<?= url('admin/payments.php') ?>">Payments</a>
            <a href="<?= url('admin/certificates.php') ?>">Certificates</a>
            <a href="<?= url('admin/reports.php') ?>">Reports</a>
            <a href="<?= url('admin/contact-messages.php') ?>">Contact Messages</a>
        </nav>
    </aside>
    <div>
        <h3>Quick Actions</h3>
        <p><a href="<?= url('admin/users.php?action=add') ?>" class="btn btn-primary">Add User</a></p>
        <p><a href="<?= url('admin/courses.php?status=pending_review') ?>" class="btn btn-primary">Review Pending Courses</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
