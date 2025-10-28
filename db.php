<?php
// db.php
$host = 'localhost';
$db = 'web_procesador';
$user = 'marc';
$pass = 'Y3CF7B9xoRWz!D@u'; // XAMPP por defecto
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
try {
$pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
die('DB ERROR: ' . $e->getMessage());
}