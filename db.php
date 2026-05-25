<?php
$url = getenv('MYSQL_URL');

if ($url) {
    $parts = parse_url($url);
    $host = $parts['host'];
    $port = $parts['port'] ?? 3306;
    $db   = ltrim($parts['path'], '/');
    $user = $parts['user'];
    $pass = $parts['pass'];
} else {
    $host = '127.0.0.1';
    $port = 3306;
    $db   = 'snowbase';
    $user = 'root';
    $pass = '';
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
