<?php
// download_doc.php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }


$id = $_GET['id'] ?? null;
if (!$id) exit('No id');


$stmt = $pdo->prepare('SELECT * FROM archivos WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) exit('No existe');


// Si no admin, comprobar que sea suyo
if ($_SESSION['rol'] !== 'admin' && $_SESSION['niu'] !== $a['usuario_niu']) exit('Sin permiso');


header('Content-Description: File Transfer');
header('Content-Type: ' . ($a['mime'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename=');