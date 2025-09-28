<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol_input = $_POST['rol'] ?? '';

if (!$email || !$password || !$rol_input) {
    echo "Faltan datos. <a href='login.php'>Volver</a>";
    exit;
}

/* Buscar usuario */
$stmt = $conn->prepare("SELECT ID_usuario, `password`, ID_rol FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Usuario no encontrado. <a href='login.php'>Volver</a>";
    $stmt->close();
    $conn->close();
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();

/* Verificar contraseña */
$hash = $user['password'];
if (!password_verify($password, $hash)) {
    echo "Contraseña incorrecta. <a href='login.php'>Volver</a>";
    $conn->close();
    exit;
}

/* Obtener nombre de rol desde la tabla rol */
$role_id = intval($user['ID_rol']);
$stmt = $conn->prepare("SELECT Rol_nombre FROM rol WHERE ID_rol = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$res2 = $stmt->get_result();
if ($res2->num_rows === 0) {
    echo "Rol no encontrado. Contacte al administrador.";
    $stmt->close();
    $conn->close();
    exit;
}
$rol_nombre = strtolower($res2->fetch_assoc()['Rol_nombre']);
$stmt->close();

/* Comparar rol seleccionado en el formulario con el rol real del usuario */
if ($rol_nombre !== strtolower($rol_input)) {
    echo "Rol incorrecto para este usuario. <a href='login.php'>Volver</a>";
    $conn->close();
    exit;
}

/* Iniciar sesión y redirigir según rol */
$_SESSION['email'] = $email;
$_SESSION['rol'] = $rol_nombre;

$conn->close();

if ($rol_nombre === 'docente') {
    header("Location: index.php");
    exit;
} elseif ($rol_nombre === 'alumno') {
    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>