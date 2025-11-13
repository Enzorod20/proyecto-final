<?php
$host = "localhost";
$user = "root";
$passdb = "";
$db = "proyecto_final";

$conn = mysqli_connect($host, $user, $passdb, $db);
if(!$conn){
    die("No hay conexion: ". mysqli_connect_error());
}
?>
