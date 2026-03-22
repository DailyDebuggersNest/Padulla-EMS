<?php
// config/db.php

// Define BASE_PATH dynamically to allow renaming the EMS folder
if (!defined('BASE_PATH')) {
    $r = str_replace('\\', '/', dirname(__DIR__));
    $d = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $basePath = '/';
    if (!empty($d) && strpos($r, $d) === 0) {
        $basePath = substr($r, strlen($d));
    }
    // Fallback if Document Root is not perfectly matched (e.g. some alias setups)
    if (empty($d) || strpos($r, $d) !== 0) {
        // Fallback: extract the directory name of the project root
        $basePath = '/' . basename($r);
    }
    
    $basePath = rtrim($basePath, '/') . '/';
    define('BASE_PATH', $basePath);
}

$host = '127.0.0.1';
$db   = 'ems_db25';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
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
?>
