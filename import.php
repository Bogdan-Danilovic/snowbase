<?php
require_once __DIR__ . '/db.php';
$sql = file_get_contents(__DIR__ . '/snowbase_dump.sql');
$pdo->exec($sql);
echo "Import OK - obrisi import.php i snowbase_dump.sql sada!";
