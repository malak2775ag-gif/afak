<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireLogin();

$certId = (int)($_GET['id'] ?? 0);
if (!$certId) {
    die('Invalid certificate ID.');
}

// Fetch certificate details and ensure it belongs to the logged-in student and is approved
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, co.title as course_name,
           inst.first_name as inst_first, inst.last_name as inst_last
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    JOIN courses co ON c.course_id = co.id
    JOIN users inst ON co.instructor_id = inst.id
    WHERE c.id = ? AND c.student_id = ? AND c.status = 'approved'
");
$stmt->execute([$certId, $_SESSION['user_id']]);
$cert = $stmt->fetch();

if (!$cert) {
    die('Certificate not found or not yet approved.');
}

// Path to background image
$bgPath = __DIR__ . '/assets/img/certificate.png';
if (!file_exists($bgPath)) {
    die('Certificate template (assets/img/certificate.png) is missing.');
}

// Create image resource from the template
$image = @imagecreatefrompng($bgPath);
if (!$image) {
    die('Failed to load certificate background image. Ensure assets/img/certificate.png exists.');
}

// Define colors
$textColor   = imagecolorallocate($image, 33, 37, 41);   // Dark Charcoal
$accentColor = imagecolorallocate($image, 184, 134, 11); // Elegant Gold
$navyColor   = imagecolorallocate($image, 26, 35, 126);   // Professional Navy
$lightGray   = imagecolorallocate($image, 100, 100, 100); // For meta info

$imgWidth = imagesx($image);
$imgHeight = imagesy($image);

// Add Logo (assuming assets/img/AFAKLOGO.jpg exists based on other pages)
$logoPath = __DIR__ . '/assets/img/AFAKLOGO.jpg';
if (file_exists($logoPath) && ($logo = @imagecreatefromjpeg($logoPath))) {
    $logoW = imagesx($logo);
    $logoH = imagesy($logo);
    $newW = (int)($imgWidth * 0.12); // Logo is 12% of certificate width
    $newH = (int)($logoH * ($newW / $logoW));
    $logoY = (int)($imgHeight * 0.08); // Position at 8% height
    imagecopyresampled($image, $logo, (int)(($imgWidth - $newW) / 2), $logoY, 0, 0, $newW, $newH, $logoW, $logoH);
    imagedestroy($logo);
}


// Details to print
$studentName = mb_strtoupper($cert['first_name'] . ' ' . $cert['last_name']);
$courseName = $cert['course_name'];
$instructorName = $cert['inst_first'] . ' ' . $cert['inst_last'];
$date = date('d/m/Y', strtotime($cert['issued_at'] ?? $cert['created_at'])); // DD/MM/YYYY format
$code = $cert['certificate_code'];

// Font file path (Adjust this path to a .ttf file in your project assets, e.g., assets/fonts/Roboto-Bold.ttf)
$fontPath = realpath(__DIR__ . '/assets/fonts/Roboto-Bold.ttf');
$fontPathRegular = realpath(__DIR__ . '/assets/fonts/Roboto-Regular.ttf');

// Check if font files exist, otherwise fallback
if (!$fontPath || !is_readable($fontPath)) {
    $fontPath = null; // Use internal fonts
}
if (!$fontPathRegular || !is_readable($fontPathRegular)) {
    $fontPathRegular = null; // Use internal fonts
}

// Test if GD can actually read the font files (sometimes file_exists is true but GD fails)
if ($fontPath && !@imagettfbbox(10, 0, $fontPath, 'test')) {
    $fontPath = null;
    $fontPathRegular = null;
}

if ($fontPath && $fontPathRegular) {
    // Helper to draw text centered horizontally
    $drawCentered = function($size, $yPercent, $text, $font = null, $color = null) use ($image, $textColor, $fontPath, $imgWidth, $imgHeight) {
        $actualFont = $font ?? $fontPath; // Default to bold if not specified
        $actualColor = $color ?? $textColor;
        $box = @imagettfbbox($size, 0, $actualFont, $text);
        if (!$box) return; 
        $textWidth = abs($box[2] - $box[0]);
        $x = (int)(($imgWidth - $textWidth) / 2);
        $y = (int)($imgHeight * $yPercent);
        @imagettftext($image, $size, 0, $x, $y, $actualColor, $actualFont, $text);
    };

    // Helper to draw text centered at a specific X point
    $drawAtPoint = function($size, $centerX, $y, $text, $font, $color) use ($image) {
        $box = @imagettfbbox($size, 0, $font, $text);
        if (!$box) return;
        $w = abs($box[2] - $box[0]);
        imagettftext($image, $size, 0, $centerX - ($w / 2), $y, $color, $font, $text);
    };

    // Calculate relative sizes based on image width to keep it consistent
    $baseScale = $imgWidth / 2000;

    // 1. Title
    $drawCentered(65 * $baseScale, 0.25, "CERTIFICATE OF COMPLETION", $fontPath, $accentColor);
    
    // 2. Formal Introduction
    $drawCentered(28 * $baseScale, 0.33, "This is to certify that", $fontPathRegular, $lightGray);
    
    // 3. Recipient Name (The main focus)
    $drawCentered(90 * $baseScale, 0.45, $studentName, $fontPath, $navyColor);
    
    // 4. Achievement Statement
    $drawCentered(26 * $baseScale, 0.53, "has successfully completed the professional development course", $fontPathRegular, $textColor);
    
    // 5. Course Title
    $drawCentered(55 * $baseScale, 0.63, $courseName, $fontPath, $textColor);

    // 6. Organization Context
    $drawCentered(26 * $baseScale, 0.71, "offered by AFAK Learning Platform", $fontPathRegular, $lightGray);

    // 7. Metadata (Date & ID)
    $drawCentered(22 * $baseScale, 0.79, "Date of Issue: " . $date . "  |  Certificate ID: " . $code, $fontPathRegular, $textColor);
    
    // 8. Signature Section
    $sigY = (int)($imgHeight * 0.86);
    $lineLength = (int)($imgWidth * 0.25);
    
    // Left: Instructor Signature
    $leftX = (int)($imgWidth * 0.28);
    imageline($image, $leftX - ($lineLength/2), $sigY, $leftX + ($lineLength/2), $sigY, $textColor);
    $drawAtPoint(22 * $baseScale, $leftX, $sigY + (40 * $baseScale), "Course Instructor", $fontPathRegular, $textColor);
    $drawAtPoint(18 * $baseScale, $leftX, $sigY + (80 * $baseScale), $instructorName, $fontPathRegular, $lightGray);

    // Right: Platform Director
    $rightX = (int)($imgWidth * 0.72);
    imageline($image, $rightX - ($lineLength/2), $sigY, $rightX + ($lineLength/2), $sigY, $textColor);
    $drawAtPoint(22 * $baseScale, $rightX, $sigY + (40 * $baseScale), "Platform Director", $fontPathRegular, $textColor);
    $drawAtPoint(18 * $baseScale, $rightX, $sigY + (80 * $baseScale), "Academic Affairs", $fontPathRegular, $lightGray);

    // 9. Footer Verification
    $drawCentered(18 * $baseScale, 0.92, "Scan to verify this document: " . url("verify.php?code=" . $code), $fontPathRegular, $accentColor);

} else {
    // Fallback to internal GD fonts if TTF file is not available
    $baseFontSize = 5; // Max size for imagestring
    $smallFontSize = 3;
    $lineHeight = 30; // Approximate line height for internal fonts

    // Helper to center text horizontally for fallback fonts
    $drawCenteredFallback = function($size, $y, $text, $color = null) use ($image, $textColor, $imgWidth) {
        $actualColor = $color ?? $textColor;
        $textWidth = strlen($text) * imagefontwidth($size);
        $x = (int)(($imgWidth - $textWidth) / 2);
        imagestring($image, $size, $x, $y, $text, $actualColor);
    };

    $currentY = 200;
    $drawCenteredFallback($baseFontSize, $currentY, "CERTIFICATE OF COMPLETION", $accentColor);
    $currentY += $lineHeight * 2;

    $drawCenteredFallback($smallFontSize, $currentY, "This is to certify that", $textColor);
    $currentY += $lineHeight;

    $drawCenteredFallback($baseFontSize, $currentY, $studentName, $navyColor);
    $currentY += $lineHeight * 2;

    $drawCenteredFallback($smallFontSize, $currentY, "has successfully completed the course", $textColor);
    $currentY += $lineHeight;

    $drawCenteredFallback($baseFontSize, $currentY, $courseName, $textColor);
    $currentY += $lineHeight;

    $drawCenteredFallback($smallFontSize, $currentY, "offered by AFAK Learning Platform", $textColor);
    $currentY += $lineHeight * 2;

    $drawCenteredFallback($smallFontSize, $currentY, "Date: " . $date . "  |  Certificate ID: " . $code, $textColor);
    $currentY += $lineHeight * 2;

    // Signature Section (Fallback)
    $sigY = $currentY;
    $lineLength = 150; // Shorter line for fallback
    
    // Left: Instructor Signature
    $leftX = (int)($imgWidth * 0.25);
    imageline($image, $leftX - ($lineLength/2), $sigY, $leftX + ($lineLength/2), $sigY, $textColor);
    $drawCenteredFallback($smallFontSize, $sigY + 5, "Instructor Signature", $textColor);

    // Right: Platform Signature
    $rightX = (int)($imgWidth * 0.75);
    imageline($image, $rightX - ($lineLength/2), $sigY, $rightX + ($lineLength/2), $sigY, $textColor);
    $drawCenteredFallback($smallFontSize, $sigY + 5, "Platform Director", $textColor);
    $currentY = $sigY + $lineHeight * 2;

}

// Set headers to serve the image directly to the browser
header('Content-Type: image/png');
header('Content-Disposition: inline; filename="Certificate_' . $code . '.png"');

// Render image
imagepng($image);

// Free memory
imagedestroy($image);
exit;