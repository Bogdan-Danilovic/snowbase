<?php
$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($url) {
    $parts = parse_url($url);
    $host = $parts['host'];
    $port = $parts['port'] ?? 3306;
    $db   = ltrim($parts['path'], '/');
    $user = $parts['user'];
    $pass = $parts['pass'];
} else {
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
    $port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: 3306;
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'snowbase';
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
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


cat > /c/xampp/htdocs/snowbase4.3/db.php << 'EOF'
<?php
$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($url) {
    $parts = parse_url($url);
    $host = $parts['host'];
    $port = $parts['port'] ?? 3306;
    $db   = ltrim($parts['path'], '/');
    $user = $parts['user'];
    $pass = $parts['pass'];
} else {
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
    $port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: 3306;
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'snowbase';
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
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
