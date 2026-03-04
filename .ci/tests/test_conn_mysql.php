<?php
$dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
    getenv('MYSQL_HOST') ?: '127.0.0.1',
    getenv('MYSQL_PORT') ?: '3306',
    getenv('MYSQL_DATABASE') ?: 'testdb'
);
$user = getenv('MYSQL_USER') ?: 'mysql';
$pass = getenv('MYSQL_PASSWORD') ?: 'Test123';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $val = $pdo->query('SELECT 1')->fetchColumn();
    if ((int)$val === 1) {
        echo "MySQL OK\n";
        exit(0);
    }
    throw new RuntimeException('Unexpected SELECT 1 result');
} catch (Throwable $e) {
    fwrite(STDERR, "MySQL test failed: " . $e->getMessage() . "\n");
    exit(1);
}