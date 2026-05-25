<?php
require_once 'db.php';
$sql = file_get_contents(__DIR__ . '/snowbase_dump.sql');
$pdo->exec($sql);
echo "Import OK";