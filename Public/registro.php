
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Registro</title>
</head>
<body class="container">
    <h1 class="text-2xl font-semibold mb-4">Registro de usuario</h1>
    <form method="post" action="guardar_registro.php" class="max-w-md bg-white p-6 rounded shadow">
       <label class="block mb-3"> Nombre:
        <input class="w-full border px-2 py-1 rounded" type="text" name="nombre" autocomplete="off"  required>
        </label>
       <label class="block mb-3"> Apellido:
        <input class="w-full border px-2 py-1 rounded" type="text" name="apellido" autocomplete="off"  required>
        </label>
        <label class="block mb-3"> Correo:
        <input class="w-full border px-2 py-1 rounded" type="email" name="email" autocomplete="off" placeholder="ejemplo@correo.com" required>
        </label>
        <label class="block mb-3"> Contraseña:
        <input class="w-full border px-2 py-1 rounded" type="password" name="password" placeholder="contraseña" required>
        </label>
        <div class="mb-4">
            <label class="inline-flex items-center mr-4"><input type="radio" name="rol" value="docente" required> <span class="ml-2">Docente</span></label>
            <label class="inline-flex items-center"><input type="radio" name="rol" value="alumno" required> <span class="ml-2">Alumno</span></label>
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Registrarse</button>
    </form>
</body>
</html>