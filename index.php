<?php
// index.php
session_start();
require 'db.php';

// Si no está logueado, redirige
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$niu = $_SESSION['niu'];
$rol = $_SESSION['rol'];

// Lista de archivos: si admin -> todos, si cliente -> solo sus propios
if ($rol === 'admin') {
    $stmt = $pdo->query('SELECT * FROM archivos ORDER BY uploaded_at DESC');
    $archivos = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM archivos WHERE usuario_niu = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$niu]);
    $archivos = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel principal</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <h2>Panel - Bienvenido <?php echo htmlspecialchars($_SESSION['niu']); ?> (<?php echo htmlspecialchars($rol); ?>)</h2>
    <p>
        <a href="logout.php">Salir</a>
        <?php if ($rol === 'admin'): ?>
            | <a href="crear_usuario.php">Crear usuario</a>
        <?php endif; ?>
    </p>

<h3>Subir documento (.doc, .docx)</h3>
<form action="upload.php" method="post" enctype="multipart/form-data">
<input type="file" name="docfile" accept=".doc,.docx" required>
<button type="submit">Subir y procesar</button>
</form>


<h3>Archivos</h3>
<table border="1" cellpadding="4">
<tr><th>NIU</th><th>Archivo</th><th>Subido</th><th>Estado</th><th>Procesado (TXT)</th><th>Acciones</th></tr>
<?php foreach ($archivos as $a): ?>
<tr>
<td><?php echo htmlspecialchars($a['usuario_niu']); ?></td>
<td><?php echo htmlspecialchars($a['filename']); ?></td>
<td><?php echo $a['uploaded_at']; ?></td>
<td><?php echo $a['status']; ?></td>
<td><?php echo $a['procesado_path'] ? htmlspecialchars($a['procesado_path']) : '-'; ?></td>
<td>
<?php if ($a['procesado_path']): ?>
<a href="<?php echo htmlspecialchars($a['procesado_path']); ?>" download>Descargar TXT</a>
| <a href="<?php echo htmlspecialchars($a['procesado_path']); ?>" target="_blank">Leer</a>
<?php else: ?>
-
<?php endif; ?>
| <a href="download_doc.php?id=<?php echo $a['id']; ?>">Descargar DOC</a>
</td>
</tr>
<?php endforeach; ?>
</table>


</body></html>