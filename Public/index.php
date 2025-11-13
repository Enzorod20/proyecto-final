<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$rol = strtolower($_SESSION['rol'] ?? '');
$usuario = $_SESSION['email'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Inicio</title>
</head>
<body class="container">
    <header>
        <div>Conectado como: <?= htmlspecialchars($usuario) ?> — Rol: <?= htmlspecialchars($rol) ?></div>
        <form action="logout.php" method="post" style="margin:0">
            <button type="submit">Cerrar sesión</button>
        </form>
    </header>

    <main>
        <?php
        if ($rol === 'docente') {
            header('Location: docente_panel.php');
        } elseif ($rol === 'alumno') {
            header('Location: alumno_panel.php');
        } elseif ($rol === 'admin') {
            header('Location: admin.php');
        } else {
            echo '<h1>Panel general</h1><p>Rol no reconocido. Contacta al administrador.</p>';
        }
        exit;
        ?>
    </main>
</body>
</html>