<?php
$dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
    getenv('PGHOST') ?: 'postgres',
    getenv('PGPORT') ?: '5432',
    getenv('PGDATABASE') ?: 'postgres'
);
$user = getenv('PGUSER') ?: 'postgres';
$pass = getenv('PGPASSWORD') ?: '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $val = $pdo->query('SELECT 1')->fetchColumn();
    if ((int)$val === 1) {
        echo "PostgreSQL OK\n";
        exit(0);
    }
    throw new RuntimeException('Unexpected SELECT 1 result');
} catch (Throwable $e) {
    fwrite(STDERR, "PG test failed: " . $e->getMessage() . "\n");
    exit(1);
}