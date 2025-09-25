<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion</title>
</head>
<body>
    <h1>Bienvenido</h1>
    <form method="post" action="validar.php">
       <label> Correo:
        <input type="email" name="email" autocomplete="off" placeholder="ejemplo@correo.com" required>
        </label><br>

        <label>
        Contraseña:
        <input type="password" name="password" placeholder="contraseña" required>
        </label><br>

        <input type="radio" name="rol" value="docente" requiered>Docente
        <input type="radio" name="rol" value="alumno" required>Alumno
        <br>
        <input type="submit" value="Iniciar sesion">
    </form>
    <a href="registro.php">
    <button>Registrarse</button>
    </a>
</body>
</html>