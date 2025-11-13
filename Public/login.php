<?php
session_start();
if (isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../src/Auth.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol_input = $_POST['rol'] ?? '';

    if (!$email || !$password) {
        $error = 'Faltan datos obligatorios';
    } else {
        $result = Auth::login($email, $password, $rol_input);
        if ($result['success']) {
            $userRow = $result['user'];
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = strtolower($userRow['Rol_nombre'] ?? '');
            $_SESSION['nombre'] = $userRow['nombre'] ?? '';
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Iniciar Sesión</title>
</head>
<body class="container">
    <h1>Bienvenido</h1>
    <?php
        if ($error) {
            echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
        }
    ?>


    <form method="post">
        <label>
            <span id="emailLabel">Correo:</span>
            <input type="email" name="email" required autocomplete="off" id="emailInput"
                placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </label>

        <label>
            Contraseña:
            <input type="password" name="password" required placeholder="contraseña">
        </label>

        <div class="roles">
            <label>
                <input type="radio" name="rol" value="docente" required 
                    <?= isset($_POST['rol']) && $_POST['rol'] === 'docente' ? 'checked' : '' ?>>
                Docente
            </label>
            <label>
                <input type="radio" name="rol" value="alumno" required
                    <?= isset($_POST['rol']) && $_POST['rol'] === 'alumno' ? 'checked' : '' ?>>
                Alumno
            </label>
            <label>
                <input type="radio" name="rol" value="admin" required
                    <?= isset($_POST['rol']) && $_POST['rol'] === 'admin' ? 'checked' : '' ?>>
                Administrador
            </label>
        </div>

        <button type="submit">Iniciar sesión</button>
    </form>

    <script>
        (function(){ //una funcion de javascript hecha con IA porque tenia problema al poner primero el correo y luego seleccionar el rol del usuario
            const radios = document.querySelectorAll('input[name="rol"]');
            const emailInput = document.getElementById('emailInput');
            const emailLabel = document.getElementById('emailLabel');
            let prevEmail = emailInput.value || '';

            function onChange(evt) {
                const val = this.value;
                if (val === 'admin') {
                    if (emailInput.value && emailInput.value !== 'admin') prevEmail = emailInput.value;
                    emailInput.type = 'text';
                    emailInput.placeholder = 'admin';
                    emailLabel.textContent = 'Usuario:';
                    emailInput.value = 'admin';
                } else {
                    emailInput.type = 'email';
                    emailInput.placeholder = 'ejemplo@correo.com';
                    emailLabel.textContent = 'Correo:';
                    if (emailInput.value === 'admin') {
                        emailInput.value = prevEmail;
                    }
                }
            }

            radios.forEach(radio => radio.addEventListener('change', onChange));
        })();
    </script>

    <p>¿No tienes cuenta? <a href="registro.php">Registrate aca</a></p>
</body>
</html>