<?php
session_start();
session_unset();
session_destroy();
echo"Sesion cerrada. <a href='login.php'>volver a inciar sesion</a>";