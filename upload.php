<?php
// upload.php
session_start();
require 'db.php';

// Verificar sesión
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$niu = $_SESSION['niu'] ?? null;
if ($niu === null) {
    die('Error: sesión sin NIU. Vuelve a iniciar sesión.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['docfile'])) {
    $f = $_FILES['docfile'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        die('Error al subir archivo.');
    }

    $fname = basename($f['name']);
    $mime = $f['type'];
    $data = file_get_contents($f['tmp_name']);

    // Guardar en la base de datos
    $stmt = $pdo->prepare('INSERT INTO archivos (usuario_niu, filename, mime, doc_blob, status) VALUES (?, ?, ?, ?, "subido")');
    $stmt->execute([$niu, $fname, $mime, $data]);
    $file_id = $pdo->lastInsertId();

    // Crear carpeta uploads si no existe
    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);

    $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $fname);
    $tmp_path = $uploads_dir . '/' . $file_id . '_' . $safe_name;
    file_put_contents($tmp_path, $data);

    // Actualizar estado a procesando
    $pdo->prepare('UPDATE archivos SET status = ? WHERE id = ?')->execute(['procesando', $file_id]);

    // Carpeta de salida para el procesado
    $processed_dir = __DIR__ . '/procesados';
    if (!is_dir($processed_dir)) mkdir($processed_dir, 0777, true);

    $out_path = $processed_dir . '/' . $file_id . '.txt';

    // Ejecutar Python (solo ruta DOC y ruta salida)
    $cmd = 'python "' . __DIR__ . '/process_doc.py" ' .
           escapeshellarg($tmp_path) . ' ' .
           escapeshellarg($out_path);

    exec($cmd . ' 2>&1', $output, $return_var);

    if ($return_var === 0) {
        $relative_out = 'procesados/' . $file_id . '.txt';
        $pdo->prepare('UPDATE archivos SET status = ?, procesado_path = ?, error_msg = NULL WHERE id = ?')
            ->execute(['procesado', $relative_out, $file_id]);
    } else {
        $err = implode("\n", $output);
        $pdo->prepare('UPDATE archivos SET status = ?, error_msg = ? WHERE id = ?')
            ->execute(['error', $err, $file_id]);
        echo "<pre>Error al procesar el archivo:\n$err</pre>";
        exit;
    }

    header('Location: index.php');
    exit;
}
?>
