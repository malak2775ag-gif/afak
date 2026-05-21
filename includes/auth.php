<?php

   
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php') . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireStudent(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'student') {
        header('Location: ' . getDashboardUrl());
        exit;
    }
}

function requireInstructor(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'instructor') {
        header('Location: ' . getDashboardUrl());
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . getDashboardUrl());
        exit;
    }
}

function getDashboardUrl(): string {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'admin') return url('admin/index.php');
    if ($role === 'instructor') return url('instructor/index.php');
    return url('dashboard.php');
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, role, avatar_url, bio FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function redirectIfLoggedIn(string $to = ''): void {
    if (isLoggedIn()) {
        header('Location: ' . ($to ?: getDashboardUrl()));
        exit;
    }
}
