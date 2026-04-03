<?php

session_start();

//DAtabase configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123qwe');
define('DB_NAME', 'e_commerce_db');

//SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'ace778256@gmail.com');
define('SMTP_PASS', 'tixvtdhmvvkuzdfp');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

function getPDOConnection(): PDO
{
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

$pdo = getPDOConnection();

$protocal = (!empty($_SERVER['HTTPS'])
              && $_SERVER['HTTPS'] !=='off') ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];

$base_url = $protocal . $domain . '/ecomerce_1132026';
?>