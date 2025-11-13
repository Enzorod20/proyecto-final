<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Clases/Usuario.php';

class Auth
{

    public static function register(array $data): array
    {
        $conn = Database::getConnection();

        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $rol = strtolower(trim($data['rol'] ?? ''));

        if (!$nombre || !$apellido || !$email || !$password || !in_array($rol, ['docente','alumno','admin'])) {
            return ['success' => false, 'message' => 'Faltan datos o rol inválido'];
        }

        $stmt = $conn->prepare("SELECT ID_usuario FROM usuario WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'El correo ya está registrado.'];
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT ID_rol FROM rol WHERE LOWER(Rol_nombre) = ?");
        $stmt->bind_param('s', $rol);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $role_id = intval($row['ID_rol']);
            $stmt->close();
        } else {
            $stmt->close();
            $stmt2 = $conn->prepare("INSERT INTO rol (Rol_nombre) VALUES (?)");
            if (!$stmt2) {
                return ['success' => false, 'message' => 'Error al preparar inserción de rol: ' . $conn->error];
            }
            $rolProper = ucfirst($rol);
            $stmt2->bind_param('s', $rolProper);
            if (!$stmt2->execute()) {
                $stmt2->close();
                return ['success' => false, 'message' => 'Error al crear rol: ' . $conn->error];
            }
            $role_id = intval($conn->insert_id);
            $stmt2->close();
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuario (nombre, apellido, email, password, ID_rol) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error en la preparación de la consulta: ' . $conn->error];
        }
        $stmt->bind_param('ssssi', $nombre, $apellido, $email, $hash, $role_id);
        if ($stmt->execute()) {
            $newId = intval($conn->insert_id);
            $stmt->close();
            return ['success' => true, 'message' => 'Registro exitoso.', 'ID_usuario' => $newId];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al registrar: ' . $err];
    }

    public static function login(string $email, string $password, string $rol_input): array
    {
        $conn = Database::getConnection();

        if ($rol_input === 'admin' && $email === 'admin') {
            $stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.`password`, u.ID_rol, r.Rol_nombre
                FROM usuario u
                LEFT JOIN rol r ON u.ID_rol = r.ID_rol
                WHERE r.Rol_nombre = 'admin'");
        } else {
            $stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.`password`, u.ID_rol, r.Rol_nombre
                FROM usuario u
                LEFT JOIN rol r ON u.ID_rol = r.ID_rol
                WHERE u.email = ?");
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }
        $userRow = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($password, $userRow['password'])) {
            return ['success' => false, 'message' => 'Contraseña incorrecta.'];
        }

        $rol_nombre = strtolower($userRow['Rol_nombre'] ?? '');
        if ($rol_nombre !== strtolower($rol_input)) {
            return ['success' => false, 'message' => 'Rol incorrecto para este usuario.'];
        }


    $userObj = \Clases\Usuario::createFromRow($userRow);

        return ['success' => true, 'message' => 'Login correcto.', 'user' => $userRow, 'userObj' => $userObj];
    }
}

?>