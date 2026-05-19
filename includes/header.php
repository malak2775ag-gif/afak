<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>AFAK Learning Platform</title>
    <!-- the logo in the  link -->
    <link rel="icon" type="image/png" href="<?= url('assets/img/AFAKLOGO.jpg') ?>">

    <!-- css -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">

    <?php if (!empty($pageStylesheets) && is_array($pageStylesheets)): ?>
        <?php foreach ($pageStylesheets as $sheetHref): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string) $sheetHref, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach ?>
    <?php endif ?>

    <?php
    $afakScriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (strpos($afakScriptPath, '/instructor/') !== false): ?>
        <link rel="stylesheet" href="<?= url('assets/css/instructor.css') ?>">
    <?php endif ?>

    <!-- google fonts link -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
    /* sticky header and the scroll behavior */
    .site-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .site-header.scrolled .nav-links .btn-outline-light {
        color: var(--accent);
        border-color: var(--accent);
    }
    .site-header.scrolled .nav-links .btn-outline-light:hover {
        color: var(--white);
        background-color: var(--accent);
    }


    /* nav animation */
    .nav-links a:not(.btn) {
        position: relative;
        padding-bottom: 5px;
    }
    .nav-links a:not(.btn)::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 2px;
        background-color: var(--accent);
        transition: width 0.3s ease-out;
    }
    .nav-links a:not(.btn):hover::after {
        width: 100%;
    }
    .main-content {
        padding-top: 100px;
    }

    /* dashboard header */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .user-welcome {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .user-welcome .section-title {
        margin-bottom: 0;
    }
    .user-avatar-large {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }
    .settings-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        transition: transform 0.2s ease-in-out;
    }
    .settings-link:hover .settings-icon {
        transform: rotate(45deg);
    }

    /* to make the nave responsive */
    .nav-toggle {
        display: none; 
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
        z-index: 1001;
    }
    .hamburger {
        display: block;
        position: relative;
        width: 24px;
        height: 2px;
        background: #fff;
        transition: background 0.2s ease-in-out;
    }
    .hamburger::before,
    .hamburger::after {
        content: '';
        position: absolute;
        left: 0;
        width: 100%;
        height: 2px;
        background: #fff;
        transition: transform 0.2s ease-in-out, top 0.2s ease-in-out;
    }
    .hamburger::before { top: -8px; }
    .hamburger::after { top: 8px; }

    /* animation for X in the nav */
    .nav-toggle.active .hamburger {
        background: transparent;
    }
    .nav-toggle.active .hamburger::before {
        transform: rotate(45deg);
        top: 0;
    }
    .nav-toggle.active .hamburger::after {
        transform: rotate(-45deg);
        top: 0;
    }

    @media (max-width: 992px) {
        .nav-toggle {
            display: block;
        }
        .nav-links {
            position: fixed;
            top: 0;
            right: -100%;
            width: 280px;
            height: 100vh;
            background: #0a2744;
            border-left: 1px solid #dee2e6;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            flex-direction: column;
            align-items: flex-start;
            padding: 100px 2rem 2rem;
            transition: right 0.35s ease-in-out;
        }
        .nav-links.active {
            right: 0;
        }
        .nav-links > * {
            margin-bottom: 1.2rem;
        }
        .nav-links a:not(.btn), .nav-links .text-muted {
            color: #fff;
        }
        .nav-links a.btn {
            width: 100%;
            text-align: center;
        }
    }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">

        <!-- for logo img -->
        <a href="<?= url('index.php') ?>" class="logo">
            <?php if (file_exists(__DIR__ . '/../assets/img/AFAKLOGO.jpg')): ?>
                <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK">
            <?php endif; ?>
            AFAK
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation"><span class="hamburger"></span></button>

        <!-- nav -->
        <nav class="nav-links">
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('courses.php') ?>">Courses</a>
            <a href="<?= url('about.php') ?>">About</a>
            <a href="<?= url('contact.php') ?>">Contact</a>

            <?php if (isLoggedIn()): ?>
                <a href="<?= url('chatbot.php') ?>">Chatbot</a>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= url('admin/index.php') ?>">Admin</a>
                <?php elseif ($_SESSION['role'] === 'instructor'): ?>
                    <a href="<?= url('instructor/index.php') ?>">Instructor</a>
                <?php else: ?>
                    <a href="<?= url('dashboard.php') ?>">Dashboard</a>
                <?php endif; ?>

                <!-- <a href="<?= url('profile.php') ?>">Profile</a> -->

                <?php
                $avatar = 'assets/img/std.jpg'; // student img defult 
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'instructor') {
                        $avatar = 'assets/img/inst.jpg'; // for instructors
                    } elseif ($_SESSION['role'] === 'admin') {
                        $avatar = 'assets/img/admin.png'; // for the admins
                    }
                }

                if (isset($user) && !empty($user['avatar_url'])) {
                    $avatar = $user['avatar_url'];
                } elseif (isset($pdo) && isset($_SESSION['user_id'])) {
                    $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $u = $stmt->fetch();
                    if ($u && !empty($u['avatar_url'])) {
                        $avatar = $u['avatar_url'];
                    }
                }
                ?>
                <div style="display: inline-flex; align-items: center; gap: 8px;">
                    <img src="<?= url($avatar) ?>" alt="Avatar" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                    <span class="text-muted"><?= e($_SESSION['first_name'] ?? '') ?> (<?= e($_SESSION['role']) ?>)</span>
                </div>

                <a href="<?= url('logout.php') ?>" class="btn btn-outline-light">Logout</a>

            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="btn btn-outline-light">Login</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary">Register</a>
            <?php endif; ?>
            
        </nav>

    </div>
</header>

<main class="main-content">