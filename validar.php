<?php
session_start();
$usuario_valido="docente@gmail.com";
$contra_valida="1234";


$email=$_POST['email'];
$password=$_POST['password'];
$rol=$_POST['rol'];


if($usuario_valido==$email && $contra_valida==$password && $rol==='docente'){
    $_SESSION['email']=$email;
    $_SESSION['rol']='docente';
    header("Location: indexprof.php");
    exit();
}else if($usuario_valido==$email && $contra_valida==$password && $rol==='alumno'){
     $_SESSION['email']=$email;
     $_SESSION['rol']='alumno';
    header("Location: indexalu.php");
    exit();
}
else{
    echo"error en inicio de sesion";  
}
?>