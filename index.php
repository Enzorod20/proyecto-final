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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inicio</title>
</head>
<body>
    <p>Conectado como: <?= htmlspecialchars($usuario) ?> — Rol: <?= htmlspecialchars($rol) ?></p>
    <form action="logout.php" method="post">
        <button type="submit">Cerrar sesión</button>
    </form>

    <?php if ($rol === 'docente'): ?>
        <h1>Panel docente</h1>
        <p>Aquí va el contenido / enlaces para docentes.</p>
        <ul>
            <li><a href="calificaciones.php">Gestionar calificaciones</a></li>
            <li><a href="clases.php">Mis clases</a></li>
        </ul>

    <?php elseif ($rol === 'alumno'): ?>
        <h1>Panel alumno</h1>
        <p>Aquí va el contenido / enlaces para alumnos.</p>
        <ul>
            <li><a href="mis_materias.php">Mis materias</a></li>
            <li><a href="ver_notas.php">Ver notas</a></li>
        </ul>

    <?php else: ?>
        <h1>Panel general</h1>
        <p>Rol no reconocido. Contacta al administrador.</p>
    <?php endif; ?>
</body>
</html>