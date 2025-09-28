<?php
require 'conexion.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: registro.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol = trim($_POST['rol'] ?? '');

if(!$nombre || !$apellido || !$email || !$password || !in_array(strtolower($rol), ['docente','alumno'])){
    echo "Faltan datos o rol inválido. <a href='registro.php'>Volver</a>";
    exit;
}

/* Verificar email único */
$stmt = $conn->prepare("SELECT ID_usuario FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows > 0){
    echo "El correo ya está registrado. <a href='login.php'>Ir a login</a>";
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

/* Obtener ID de rol desde la tabla rol; si no existe, crearla */
$rol_lower = strtolower($rol);
$stmt = $conn->prepare("SELECT ID_rol FROM rol WHERE LOWER(Rol_nombre) = ?");
$stmt->bind_param("s", $rol_lower);
$stmt->execute();
$res = $stmt->get_result();
if($res && $res->num_rows > 0){
    $role_row = $res->fetch_assoc();
    $role_id = intval($role_row['ID_rol']);
    $stmt->close();
} else {
    $stmt->close();
    // Insertar nuevo rol y obtener su ID
    $stmt2 = $conn->prepare("INSERT INTO rol (Rol_nombre) VALUES (?)");
    if(!$stmt2){
        echo "Error al asegurar rol: " . $conn->error;
        $conn->close();
        exit;
    }
    $rol_nombre_proper = ucfirst($rol_lower);
    $stmt2->bind_param("s", $rol_nombre_proper);
    if(!$stmt2->execute()){
        echo "Error al crear rol: " . $stmt2->error;
        $stmt2->close();
        $conn->close();
        exit;
    }
    $role_id = intval($conn->insert_id);
    $stmt2->close();
}

/* Hashear contraseña */
$hash = password_hash($password, PASSWORD_DEFAULT);

/* Insertar usuario con el ID de rol obtenido */
$stmt = $conn->prepare("INSERT INTO usuario (nombre, apellido, email, password, ID_rol) VALUES (?, ?, ?, ?, ?)");
if(!$stmt){
    echo "Error en la preparación de la consulta: " . $conn->error;
    $conn->close();
    exit;
}
$stmt->bind_param("ssssi", $nombre, $apellido, $email, $hash, $role_id);

if($stmt->execute()){
    echo "Registro exitoso. <a href='login.php'>Iniciar sesión</a>";
} else {
    echo "Error al registrar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>