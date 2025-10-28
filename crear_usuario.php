<?php
// crear_usuario.php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['rol'] !== 'admin') {
header('Location: login.php');
exit;
}


$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$niu = $_POST['niu'];
$nombre = $_POST['nombre'];
$password = $_POST['password'];
$rol = $_POST['rol'];


if (!preg_match('/^\d{1,7}$/', $niu)) {
$err = 'NIU debe ser hasta 7 dígitos (puede contener ceros)';
} else {
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO usuarios (niu,nombre,password,rol) VALUES (?,?,?,?)');
try {
$stmt->execute([$niu,$nombre,$hash,$rol]);
header('Location: index.php');
exit;
} catch (PDOException $e) {
$err = 'Error al crear usuario: ' . $e->getMessage();
}
}
}
?>
<!doctype html>
<html><body>
<h2>Crear usuario (admin)</h2>
<?php if ($err) echo "<p style='color:red;'>$err</p>"; ?>
<form method="post">
NIU: <input name="niu" required maxlength="7"><br>
Nombre: <input name="nombre" required><br>
Contraseña: <input name="password" type="password" required><br>
Rol: <select name="rol"><option value="cliente">cliente</option><option value="admin">admin</option></select><br>
<button type="submit">Crear</button>
</form>
<p><a href="index.php">Volver</a></p>
</body></html>