<?php

session_start();
if(!isset($_SESSION['email'])){
    header("Location:login.php");
    exit();
}

echo "Sesion inciada con exito";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio exitoso</title>
</head>
<body>
    <p>Este es el index</p>
    <form action="logout.php">
    <input type="submit" value="Cerrar sesion" />
</form>
</body>
</html>