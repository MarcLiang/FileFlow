<?php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$niu = $_SESSION['niu'];
$rol = $_SESSION['rol'];

$ticket_id = $_POST['ticket_id'] ?? null;

if (!$ticket_id) {
    die('ID de ticket no especificado.');
}

// Comprobar si el usuario puede borrar este ticket
if ($rol === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM archivos WHERE id=?");
    $stmt->execute([$ticket_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM archivos WHERE id=? AND usuario_niu=?");
    $stmt->execute([$ticket_id, $niu]);
}

$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    die('No tienes permisos para eliminar este ticket.');
}

// Borrar archivo procesado si existe
if (!empty($ticket['procesado_path']) && file_exists($ticket['procesado_path'])) {
    unlink($ticket['procesado_path']);
}

// Borrar del DB
$pdo->prepare("DELETE FROM archivos WHERE id=?")->execute([$ticket_id]);

header('Location: index.php');
exit;
?>
