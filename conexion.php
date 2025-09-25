<?php
 private $host = "localhost";
 private $db = "proyecto_final"; //nombre base de dato
 private $user = "root";
 private $passdb = "";

 $conn = mysqli_connect($host,$db,$user,$passdb);
 if(!$conn){

    die("No hay conexion:". mysqli_connect_error());
 }
