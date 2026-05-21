<?php
$configPath = __DIR__ . '/database.php';
$config = file_exists($configPath) ? require $configPath : [];

$host = getenv('DB_HOST') ?: ($config['host'] ?? 'mysql-7402dda-malak2775ag-c295.a.aivencloud.com');
$port = getenv('DB_PORT') ?: ($config['port'] ?? '24631');
$dbname = getenv('DB_NAME') ?: ($config['dbname'] ?? 'afakdb');
$user = getenv('DB_USER') ?: ($config['username'] ?? 'avnadmin');
$pass = getenv('DB_PASS') ?: ($config['password'] ?? 'AVNS_TsvGVJVAtmU8aio3wGf');

$charset = getenv('DB_CHARSET') ?: ($config['charset'] ?? 'utf8mb4');

// إعداد خيارات SSL إذا كانت مطلوبة (مهمة للربط مع قواعد البيانات السحابية)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// استخدام شهادة SSL فقط في الإنتاج أو عند الاتصال بقاعدة بيانات خارجية وليس localhost
$isLocal = ($host === '127.0.0.1' || $host === 'localhost');
if (getenv('DB_SSL_CA') === 'true' || (!$isLocal && file_exists(__DIR__ . '/../assets/ca.pem'))) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/../assets/ca.pem';
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $host, $port, $dbname, $charset
);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed for host '{$host}': " . $e->getMessage());
}
