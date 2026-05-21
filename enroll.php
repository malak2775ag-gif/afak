<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('courses.php'));
    exit;
}

$courseId = (int)($_POST['course_id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('courses.php'));
    exit;
}

// Check if course exists and if published
$stmt = $pdo->prepare("SELECT id, is_free FROM courses WHERE id = ? AND status = 'published'");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: ' . url('courses.php'));
    exit;
}

// check payment
if (!$course['is_free']) {
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE student_id = ? AND course_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    if (!$stmt->fetch()) {
        flash('danger', 'Payment required for this course.');
        header('Location: ' . url('course-detail.php?id=' . $courseId));
        exit;
    }
}

// Check if not already enrolled
$stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $courseId]);
if ($stmt->fetch()) {
    header('Location: ' . url('course-view.php?id=' . $courseId));
    exit;
}

$pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $courseId]);
flash('success', 'Successfully enrolled!');

header('Location: ' . url('course-view.php?id=' . $courseId));
exit;
