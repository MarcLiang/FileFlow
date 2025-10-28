<?php
session_start();
require "db.php";

// Solo usuarios logueados
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['procesar_tickets'])) {
    // Ejecutar Python
    $cmd = 'python "' . __DIR__ . '/process_ticket.py"';
    exec($cmd . ' 2>&1', $output, $return_var);

    echo "<pre>";
    echo htmlspecialchars(implode("\n", $output));
    echo "</pre>";

    if ($return_var === 0) {
        echo "✅ Tickets procesados correctamente.";
    } else {
        echo "❌ Hubo un error procesando tickets.";
    }
}
?>



<?php
$niu = $_SESSION['niu'];
$usuario = $_SESSION['usuario'];
$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];

// Obtener los tickets subidos por el usuario (o todos si es admin)
if ($rol === 'admin') {
    $stmt = $pdo->query("SELECT * FROM archivos ORDER BY fecha_subida DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM archivos WHERE usuario_niu = ? ORDER BY fecha_subida DESC");
    $stmt->execute([$niu]);
}

$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de tickets</title>
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
            font-family: Arial, sans-serif;
            background: var(--primary);
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: var(--text);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 50px var(--secondary);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .user {
            text-align: center;
        }

        .table th {
            background-color: var(--muted);
            color: white;
        }

        .status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .status.pendiente {
            background-color: #ffeaa7;
            color: #636e72;
        }

        .status.procesando {
            background-color: #fdcb6e;
            color: #2d3436;
        }

        .status.procesado {
            background-color: #55efc4;
            color: #00695c;
        }

        .status.error {
            background-color: #fab1a0;
            color: #c0392b;
        }

        button,
        a.boton {
            display: inline-block;
            background: var(--accent);
            color: var(--text);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        button:hover,
        a.boton:hover {
            background: var(--secondary);
            color: var(--bg);
        }

        .logout {
            float: right;
            background: #d63031;
        }

        .logout:hover {
            background: #e17055;
        }

        .proces {
            text-align: center;
        }

        .proces button {
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎫 Panel de tickets</h1>
        <p class="user">Bienvenido, <strong><?= htmlspecialchars($nombre) ?></strong> (<?= htmlspecialchars($rol) ?>)</p>
        <form method="POST" class="proces">
            <button type="submit" name="procesar_tickets">Procesar tickets pendientes</button>
        </form>
        <a href="upload.php" class="boton">➕ Añadir ticket</a>
        <a href="logout.php" class="boton logout">Cerrar sesión</a>

        <table class="table">
            <tr>
                <th>Ticket</th>
                <th>URL</th>
                <th>Estado</th>
                <th>Procesado</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No hay tickets registrados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>
                            <?php if (!empty($t['url'])): ?>
                                <?php $ticket_id = basename($t['url']); ?>
                                <a href="<?= htmlspecialchars($t['url']) ?>" target="_blank"><?= htmlspecialchars($ticket_id) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($t['url'])): ?>
                                <a href="<?= htmlspecialchars($t['url']) ?>" target="_blank">Ver ticket</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><span class="status <?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td>
                            <?php if ($t['status'] === 'procesado' && !empty($t['procesado_path'])): ?>
                                <a href="<?= htmlspecialchars($t['procesado_path']) ?>" target="_blank">📄 Ver archivo</a>
                            <?php elseif ($t['status'] === 'error'): ?>
                                ❌ <small><?= htmlspecialchars($t['error_msg']) ?></small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t['fecha_subida']) ?></td>
                        <td>
                            <form action="delete_ticket.php" method="POST" style="display:inline;">
                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                <button type="submit" onclick="return confirm('¿Seguro que quieres eliminar este ticket?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>


    </div>
</body>

</html>