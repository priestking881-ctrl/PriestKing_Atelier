<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
$pdo = getDbConnection();
$r = $pdo->query("SELECT full_name,email,role FROM users WHERE email='testuser@example.com'")->fetch();
var_dump($r);