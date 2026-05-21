<?php
declare(strict_types=1);

// Load local AI config if exists, otherwise rely on environment variables
if (file_exists(__DIR__ . '/../config/ai.php')) {
    require_once __DIR__ . '/../config/ai.php';
}
if (!defined('GROQ_API_KEY')) define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
if (!defined('AFAK_AI_CHAT_MODEL')) define('AFAK_AI_CHAT_MODEL', getenv('AFAK_AI_CHAT_MODEL') ?: 'llama-3.3-70b-versatile');

function afak_ensure_learning_profiles_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_learning_profiles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            major VARCHAR(50) NOT NULL,
            qualification_level VARCHAR(50) NOT NULL,
            interested_field VARCHAR(50) NOT NULL,
            interested_level ENUM('beginner','intermediate','advanced') NOT NULL,
            style_info_format ENUM('visual','verbal') NOT NULL,
            style_teaching ENUM('visual','verbal') NOT NULL,
            style_memory ENUM('visual','auditory') NOT NULL,
            style_data ENUM('charts','text') NOT NULL,
            style_course_type ENUM('concrete','abstract') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_learning_profiles_user
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function afak_get_learning_profile(PDO $pdo, int $userId): ?array
{
    afak_ensure_learning_profiles_table($pdo);
    $stmt = $pdo->prepare("SELECT * FROM student_learning_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function afak_student_needs_survey(PDO $pdo, int $userId): bool
{
    return afak_get_learning_profile($pdo, $userId) === null;
}

function afak_save_learning_profile(PDO $pdo, int $userId, array $data): void
{
    afak_ensure_learning_profiles_table($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO student_learning_profiles
            (user_id, major, qualification_level, interested_field, interested_level, style_info_format, style_teaching, style_memory, style_data, style_course_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            major = VALUES(major),
            qualification_level = VALUES(qualification_level),
            interested_field = VALUES(interested_field),
            interested_level = VALUES(interested_level),
            style_info_format = VALUES(style_info_format),
            style_teaching = VALUES(style_teaching),
            style_memory = VALUES(style_memory),
            style_data = VALUES(style_data),
            style_course_type = VALUES(style_course_type)
    ");
    $stmt->execute([
        $userId,
        $data['major'],
        $data['qualification_level'],
        $data['interested_field'],
        $data['interested_level'],
        $data['style_info_format'],
        $data['style_teaching'],
        $data['style_memory'],
        $data['style_data'],
        $data['style_course_type'],
    ]);
}

/**
 * Uses AI to generate personalized recommendation reasons
 */
function afak_ai_refine_recommendations(array $rankedCourses, array $profile): array
{
    if (empty($rankedCourses) || !defined('GROQ_API_KEY')) {
        return $rankedCourses;
    }

    // Prepare a summary of the student for the AI
    $studentSummary = "Major: {$profile['major']}, Interest: {$profile['interested_field']} ({$profile['interested_level']}), Style: {$profile['style_info_format']} and {$profile['style_course_type']}.";
    
    $courseList = "";
    foreach ($rankedCourses as $c) {
        $courseList .= "- ID {$c['id']}: {$c['title']} ({$c['short_description']})\n";
    }

    $prompt = "As an academic advisor, look at this student profile: $studentSummary. "
            . "For the following courses, write a one-sentence personalized 'reason' why it matches their specific learning style or background. "
            . "Return ONLY a JSON object where keys are course IDs and values are the reasons.\n\n"
            . $courseList;

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer " . GROQ_API_KEY, "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode([
            "model" => AFAK_AI_CHAT_MODEL,
            "messages" => [["role" => "user", "content" => $prompt]],
            "response_format" => ["type" => "json_object"]
        ]),
        CURLOPT_TIMEOUT => 10,
    ]);

    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    $aiData = json_decode($response, true);
    $reasons = json_decode($aiData['choices'][0]['message']['content'] ?? '{}', true);

    foreach ($rankedCourses as &$course) {
        if (isset($reasons[$course['id']])) {
            $course['recommendation_reason'] = $reasons[$course['id']];
        }
    }
    return $rankedCourses;
}

function afak_recommend_courses(PDO $pdo, int $userId, int $limit = 6): array
{
    $profile = afak_get_learning_profile($pdo, $userId);
    if (!$profile) {
        return [];
    }

    $fieldKeywordMap = [
        'it' => ['it', 'computer', 'software', 'program', 'data', 'network', 'cyber', 'web'],
        'business' => ['business', 'management', 'accounting', 'finance', 'marketing', 'hr'],
        'eng' => ['engineering', 'mechanic', 'civil', 'electrical', 'industrial', 'eng'],
        'design' => ['design', 'ui', 'ux', 'graphics', 'creative'],
        'marketing' => ['marketing', 'branding', 'seo', 'sales', 'social media'],
    ];

    $field = strtolower((string) ($profile['interested_field'] ?? ''));
    $keywords = $fieldKeywordMap[$field] ?? [];

    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name, cat.slug AS category_slug,
               (SELECT COUNT(*) FROM enrollments e2 WHERE e2.course_id = c.id) AS enrollment_count
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE c.status = 'published'
          AND c.id NOT IN (
              SELECT e.course_id FROM enrollments e WHERE e.student_id = ?
          )
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($courses === []) {
        return [];
    }

    $perfStmt = $pdo->prepare("
        SELECT
            COALESCE(AVG(aa.percent_score), 0) AS avg_score,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
        FROM enrollments e
        LEFT JOIN assessment_attempts aa ON aa.enrollment_id = e.id
        WHERE e.student_id = ?
    ");
    $perfStmt->execute([$userId]);
    $perf = $perfStmt->fetch(PDO::FETCH_ASSOC) ?: ['avg_score' => 0, 'completed_count' => 0];
    $avgScore = (float) ($perf['avg_score'] ?? 0);
    $completedCount = (int) ($perf['completed_count'] ?? 0);

    $targetLevel = (string) $profile['interested_level'];
    if ($avgScore >= 80 && $completedCount >= 2) {
        if ($targetLevel === 'beginner') {
            $targetLevel = 'intermediate';
        } elseif ($targetLevel === 'intermediate') {
            $targetLevel = 'advanced';
        }
    } elseif ($avgScore > 0 && $avgScore < 55) {
        $targetLevel = 'beginner';
    }

    $materialScoreStmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN cm.type = 'video' THEN 1 ELSE 0 END) AS video_count,
            SUM(CASE WHEN cm.type IN ('pdf','document','slide') THEN 1 ELSE 0 END) AS text_count
        FROM course_units cu
        LEFT JOIN course_materials cm ON cm.unit_id = cu.id
        WHERE cu.course_id = ?
    ");

    $ranked = [];
    foreach ($courses as $course) {
        $score = 0;
        $reasons = [];

        $titleDesc = strtolower(($course['title'] ?? '') . ' ' . ($course['description'] ?? '') . ' ' . ($course['short_description'] ?? ''));
        $catText = strtolower((string) ($course['category_name'] ?? '') . ' ' . (string) ($course['category_slug'] ?? ''));

        if ($course['level'] === $targetLevel || $course['level'] === 'all') {
            $score += 20;
            $reasons[] = 'matches your current level';
        }

        foreach ($keywords as $kw) {
            if (strpos($titleDesc, $kw) !== false || strpos($catText, $kw) !== false) {
                $score += 8;
                $reasons[] = 'matches your interested field';
                break;
            }
        }

        $materialScoreStmt->execute([$course['id']]);
        $materialStats = $materialScoreStmt->fetch(PDO::FETCH_ASSOC) ?: ['video_count' => 0, 'text_count' => 0];
        $videoCount = (int) ($materialStats['video_count'] ?? 0);
        $textCount = (int) ($materialStats['text_count'] ?? 0);
        $totalMaterials = $videoCount + $textCount;

        if ($totalMaterials > 0) {
            $visualWeight = $videoCount / $totalMaterials;
            $verbalWeight = $textCount / $totalMaterials;

            // Determine student preference based on several criteria in their profile
            $visualPrefScore = 0;
            if ($profile['style_info_format'] === 'visual') $visualPrefScore++;
            if ($profile['style_teaching'] === 'visual') $visualPrefScore++;
            if ($profile['style_memory'] === 'visual') $visualPrefScore++;
            if ($profile['style_data'] === 'charts') $visualPrefScore++;

            if ($visualPrefScore >= 2 && $visualWeight > 0.5) {
                $score += 15;
                $reasons[] = 'fits your visual learning style';
            } elseif ($visualPrefScore < 2 && $verbalWeight > 0.5) {
                $score += 15;
                $reasons[] = 'fits your text/verbal learning style';
            }
        }

        if ($profile['style_course_type'] === 'concrete' && strpos($titleDesc, 'intro') !== false) {
            $score += 6;
        }
        if ($profile['style_course_type'] === 'abstract' && (strpos($titleDesc, 'theory') !== false || strpos($titleDesc, 'concept') !== false)) {
            $score += 6;
        }

        if ($avgScore >= 70) {
            $score += 4;
            $reasons[] = 'updated using your performance trend';
        }

        // Popularity Factor: +1 point for every 5 enrollments, capped at 10 points
        $popularityScore = min(10, (int) floor(($course['enrollment_count'] ?? 0) / 5));
        if ($popularityScore > 0) {
            $score += $popularityScore;
            $reasons[] = 'popular among other students';
        }

        $course['recommendation_score'] = $score;
        $course['recommendation_reason'] = implode(', ', array_unique($reasons));
        $ranked[] = $course;
    }

    usort($ranked, static function (array $a, array $b): int {
        return ($b['recommendation_score'] <=> $a['recommendation_score']);
    });

    return array_slice($ranked, 0, max(1, $limit));
}
