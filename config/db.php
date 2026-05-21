<?php
$config = require __DIR__ . '/database.php';

// Include port in DSN if it exists in config, otherwise default to 3306
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    /*  'mysql:host=%s;dbname=%s;charset=%s', */
    $config['host'],
    $config['port'] ?? '24631',
    $config['dbname'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
