<?php
$configPath = __DIR__ . '/database.php';
$config = file_exists($configPath) ? require $configPath : [];

// استخدام متغيرات البيئة (لـ Render) أو العودة للإعدادات المحلية (لـ WAMP)
$host = getenv('DB_HOST') ?: ($config['host'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($config['port'] ?? '3306');
$dbname = getenv('DB_NAME') ?: ($config['dbname'] ?? 'afakdb');
$user = getenv('DB_USER') ?: ($config['username'] ?? 'root');
$pass = getenv('DB_PASS') ?: ($config['password'] ?? '');
$charset = getenv('DB_CHARSET') ?: ($config['charset'] ?? 'utf8mb4');
// إعداد خيارات SSL إذا كانت مطلوبة (مهمة للربط مع قواعد البيانات السحابية)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// إذا كنا في بيئة الإنتاج أو تم تحديد استخدام SSL
if (getenv('DB_SSL_CA') === 'true' || file_exists(__DIR__ . '/../assets/ca.pem')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/../assets/ca.pem';
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $host, $port, $dbname, $charset
);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
