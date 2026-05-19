<?php


function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


function formatDuration(int $seconds): string {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m} min";
}


function url(string $path): string {
    if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        return $path;
    }
    
    $base = defined('BASE_PATH') ? '/' . trim(BASE_PATH, '/') : '';
    $cleanPath = ltrim($path, '/');
    
    if (strpos($cleanPath, '../') === 0) {
        $cleanPath = substr($cleanPath, 3);
    }

    return rtrim($base, '/') . '/' . ltrim($cleanPath, '/');
}





function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function createNotification(
    PDO $pdo,
    int $userId,
    string $type,
    string $title,
    string $message,
    ?string $relatedType = null,
    ?int $relatedId = null,
    ?string $linkUrl = null
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, title, message, link_url, related_type, related_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $type, $title, $message, $linkUrl, $relatedType, $relatedId]);
}

function issueCourseCertificate(PDO $pdo, int $enrollmentId, int $studentId, int $courseId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE enrollment_id = ? LIMIT 1");
    $stmt->execute([$enrollmentId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        return $existing;
    }

    $certificateCode = 'AFAK-' . strtoupper(bin2hex(random_bytes(4)));
    $stmt = $pdo->prepare(
        "INSERT INTO certificates (
            enrollment_id,
            student_id,
            course_id,
            certificate_code,
            status,
            issued_at
        ) VALUES (?, ?, ?, ?, 'approved', NOW())"
    );
    $stmt->execute([$enrollmentId, $studentId, $courseId, $certificateCode]);

    $certificateId = (int) $pdo->lastInsertId();

    createNotification(
        $pdo,
        $studentId,
        'certificate',
        'Certificate Ready',
        'Congratulations! Your certificate is ready in My Certificates.',
        'certificate',
        $certificateId,
        url('my-certificates.php')
    );

    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE id = ? LIMIT 1");
    $stmt->execute([$certificateId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Fetch categories and arrange them in a tree structure with markers for sub-sections
 */
function getHierarchicalCategories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id ASC, name ASC");
    $all = $stmt->fetchAll();
    
    $map = [];
    foreach ($all as $cat) {
        $parent = $cat['parent_id'] ?? 0;
        $map[$parent][] = $cat;
    }

    $result = [];
    $build = function($parentId, $depth) use (&$build, &$map, &$result) {
        foreach ($map[$parentId] ?? [] as $cat) {
            $cat['display_name'] = str_repeat('— ', $depth) . $cat['name'];
            $result[] = $cat;
            $build($cat['id'], $depth + 1);
        }
    };

    $build(0, 0);
    return $result;
}
/**
 * جلب معايير التقييم (Rubric Criteria) لتقييم معين
 */
function getAssessmentRubric(PDO $pdo, int $assessmentId): array {
    $stmt = $pdo->prepare("
        SELECT id, criterion_name, description, max_score, sort_order
        FROM rubric_criteria
        WHERE assessment_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([$assessmentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * حفظ درجات التقييم (Rubric) لعمل الطالب وتحديث حالة المحاولة
 * $evaluations: مصفوفة تحتوي على [criterion_id => ['score' => X, 'feedback' => Y]]
 */
function saveRubricScores(PDO $pdo, int $attemptId, int $gradedBy, array $evaluations): void {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rubric_scores (attempt_id, criterion_id, score, feedback, graded_by, graded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                score = VALUES(score), 
                feedback = VALUES(feedback), 
                graded_by = VALUES(graded_by), 
                graded_at = NOW()
        ");
        
        foreach ($evaluations as $criterionId => $eval) {
            $stmt->execute([
                $attemptId,
                $criterionId,
                (float)($eval['score'] ?? 0),
                $eval['feedback'] ?? null,
                $gradedBy
            ]);
        }

        // حساب الإجماليات وتحديث سجل المحاولة
        $stmtAttempt = $pdo->prepare("SELECT assessment_id FROM assessment_attempts WHERE id = ?");
        $stmtAttempt->execute([$attemptId]);
        $assessmentId = (int)$stmtAttempt->fetchColumn();

        $stmtMax = $pdo->prepare("SELECT SUM(max_score) FROM rubric_criteria WHERE assessment_id = ?");
        $stmtMax->execute([$assessmentId]);
        $totalMax = (float)($stmtMax->fetchColumn() ?: 0);

        $stmtTotal = $pdo->prepare("SELECT SUM(score) FROM rubric_scores WHERE attempt_id = ?");
        $stmtTotal->execute([$attemptId]);
        $totalEarned = (float)($stmtTotal->fetchColumn() ?: 0);

        $percent = $totalMax > 0 ? ($totalEarned / $totalMax) * 100 : 0;

        $update = $pdo->prepare("
            UPDATE assessment_attempts 
            SET score = ?, max_score = ?, percent_score = ?, 
                passed = CASE WHEN ? >= (SELECT passing_score FROM assessments WHERE id = ?) THEN 1 ELSE 0 END,
                submitted_at = IFNULL(submitted_at, NOW())
            WHERE id = ?
        ");
        $update->execute([$totalEarned, $totalMax, $percent, $percent, $assessmentId, $attemptId]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
