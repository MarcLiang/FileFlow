<?php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$niu = $_SESSION['niu'] ?? null;

if ($niu === null) {
    die('Error: sesión sin NIU. Vuelve a iniciar sesión.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url_ticket'] ?? '');

    if (empty($url)) {
        $error = "Debes introducir una URL.";
    } else {
        // Guardar ticket en BD como pendiente
        $stmt = $pdo->prepare('INSERT INTO archivos (usuario_niu, url, status) VALUES (?, ?, "pendiente")');
        $stmt->execute([$niu, $url]);
        $file_id = $pdo->lastInsertId();

        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Añadir ticket</title>
    <link rel="stylesheet" href="css/upload.css">
    <style>
        :root {
            --primary: #3A7D44;
            --secondary: #2C6E91;
            --accent: #D4A017;
            --bg: #F5F7F8;
            --text: #2E2E2E;
            --muted: #6B7280;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 30px;
            text-align: center;
            border-top: 10px solid var(--secondary);
        }

        h2 {
            color: #154734;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        button:hover {
            background: var(--secondary);
        }

        .error {
            color: #c0392b;
            margin-top: 10px;
            font-weight: bold;
        }

        .back {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #154734;
            font-weight: bold;
        }

        .back:hover {
            text-decoration: underline;
        }

        .logo {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="image/uablogo.png" alt="UAB" class="logo" width="64">
        <h2>Nuevo ticket</h2>
        <form method="POST">
            <label for="url_ticket"><strong>URL del ticket:</strong></label>
            <input type="text" id="url_ticket" name="url_ticket" placeholder="https://tiquets.uab.cat/browse/TASQ-XXXXX" required>
            <button type="submit">Procesar</button>
        </form>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <a href="index.php" class="back">⬅ Volver al panel</a>
    </div>
</body>

</html>