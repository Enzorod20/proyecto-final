
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
</head>
<body>
    <h1>Ingrese datos</h1>
    <form method="post" action="guardar_registro.php">
       <label> Nombre:
        <input type="text" name="nombre" autocomplete="off"  required>
        </label><br>

       <label> Apellido:
        <input type="text" name="apellido" autocomplete="off"  required>
        </label><br>
       
        <label> Correo:
        <input type="email" name="email" autocomplete="off" placeholder="ejemplo@correo.com" required>
        </label><br>

        <label> Contraseña:
        <input type="password" name="password" placeholder="contraseña" required>
        </label><br>

        <input type="radio" name="rol" value="docente" required>Docente
        <input type="radio" name="rol" value="alumno" required>Alumno
        <br>
        <input type="submit" value="Registrarse">
    </form>
</body>
</html>