<?php
session_start();
require "db.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $niu = $_POST['niu'] ?? '';
    $password = $_POST['password'] ?? '';

    // Buscar usuario por NIU
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE NIU = ?");
    $stmt->execute([$niu]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['niu'] = $user['niu'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php");
        exit;
    } else {
        $error = "NIU o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/colores.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <h2>Iniciar sesión</h2>
    
    <form method="POST">
        
        <div class="input-group">
            <input type="text" name="niu" id="niu" placeholder=" " required>
            <label for="niu">NIU</label>
        </div>

        <div class="input-group">
            <input type="password" name="password" id="password" placeholder=" " required>
            <label for="password">Contraseña</label>
        </div>

        
        <button type="submit">Entrar</button>

        <p style="color:red;"><?= $error ?></p>
    </form>
    
</body>

</html>