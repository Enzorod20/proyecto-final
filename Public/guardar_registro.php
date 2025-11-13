<?php
require_once __DIR__ . '/../src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

$result = Auth::register($_POST);
if ($result['success']) {
    echo $result['message'] . " <a href='login.php'>Iniciar sesión</a>";
} else {
    echo htmlspecialchars($result['message']) . " <a href='registro.php'>Volver</a>";
}

?>