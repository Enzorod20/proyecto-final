<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../Clases/Admin.php';
$admin = new \Clases\Admin($_SESSION['email']);
require_once __DIR__ . '/../src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_carrera') {
        $nombre = $_POST['nombre_carrera'] ?? '';
        $res = $admin->crearCarrera($nombre);
        echo htmlspecialchars($res['message']);
    }
    if ($accion === 'registrar_usuario') {
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'rol' => $_POST['rol'] ?? ''
        ];
        $r = Auth::register($data);
        if ($r['success']) {
            $ID_usuario = intval($r['ID_usuario'] ?? 0);
            $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
            if ($ID_usuario > 0 && $ID_carrera > 0 && strtolower($data['rol']) === 'alumno') {
                $admin->asignarUsuarioACarrera($ID_usuario, $ID_carrera);
            }
        }
        echo htmlspecialchars($r['message']);
    }
    if ($accion === 'crear_materia') {
        $nombre = $_POST['nombre_materia'] ?? '';
        $carr = $_POST['carreras'] ?? [];
        $ID_docente = isset($_POST['ID_docente_materia']) ? intval($_POST['ID_docente_materia']) : null;
        $res = $admin->crearMateria($nombre, $carr, $ID_docente);
        echo htmlspecialchars($res['message']);
    }
    if ($accion === 'asignar_docente') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        $ID_docente = intval($_POST['ID_docente'] ?? 0);
        $r = $admin->asignarDocenteAMateria($ID_materia, $ID_carrera, $ID_docente);
        echo htmlspecialchars($r['message']);
    }
    if ($accion === 'inscribir_alumno') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        $ID_alumno = intval($_POST['ID_alumno'] ?? 0);
        $r = $admin->inscribirAlumno($ID_materia, $ID_carrera, $ID_alumno);
        echo htmlspecialchars($r['message']);
    }
    if ($accion === 'dar_baja_alumno') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        $ID_alumno = intval($_POST['ID_alumno'] ?? 0);
        $r = $admin->darDeBajaAlumno($ID_materia, $ID_carrera, $ID_alumno);
        echo htmlspecialchars($r['message']);
    }
    if ($accion === 'quitar_docente') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        $ID_docente = intval($_POST['ID_docente_remove'] ?? 0);
        $r = $admin->quitarDocenteDeMateria($ID_materia, $ID_carrera, $ID_docente);
        echo htmlspecialchars($r['message']);
    }
    if ($accion === 'eliminar_materia') {
        $ID_materia = intval($_POST['ID_materia_remove'] ?? 0);
        if ($ID_materia > 0) {
            $r = $admin->eliminarMateria($ID_materia);
            echo htmlspecialchars($r['message']);
        }
    }
    if ($accion === 'desvincular_materia') {
        $ID_materia = intval($_POST['ID_materia_desvinc'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera_desvinc'] ?? 0);
        if ($ID_materia > 0 && $ID_carrera > 0) {
            $r = $admin->removerMateriaDeCarrera($ID_materia, $ID_carrera);
            echo htmlspecialchars($r['message']);
        }
    }
    if ($accion === 'eliminar_usuario') {
        $ID_usuario = intval($_POST['ID_usuario'] ?? 0);
        if ($ID_usuario > 0) {
            $r = $admin->eliminarUsuario($ID_usuario);
            echo htmlspecialchars($r['message']);
        }
    }
}


$carreras = $admin->listarCarreras();
$materias = $admin->listarMaterias();
$docentes = $admin->listarDocentes();
$alumnos = $admin->listarAlumnos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Panel de Administración</title>
</head>
<body class="container">
    <header style="display:flex;justify-content:space-between;align-items:center">
        <h1>Panel de Admin</h1>
        <form action="logout.php" method="post" style="margin:0">
            <button type="submit">Cerrar sesión</button>
        </form>
    </header>
    <form method="post" action="admin.php">
        <h2>Registrar usuario</h2>
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="text" name="apellido" placeholder="Apellido" required>
        <input type="email" name="email" placeholder="Correo" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <select name="rol" id="rolSelect">
            <option value="alumno">Alumno</option>
            <option value="docente">Docente</option>
        </select>
        <label id="labelCarrera" style="display:none">Asignar carrera al alumno:
            <select name="ID_carrera" id="selectCarrera">
                <option value="">-- seleccionar carrera --</option>
                <?php foreach ($carreras as $c): ?>
                    <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" name="accion" value="registrar_usuario">Registrar</button>
    </form>

    <section style="margin-top:24px">
        <h2>Listado de carreras y sus materias</h2>
        <?php foreach ($carreras as $c):
            $materiasPor = $admin->listarMateriasPorCarrera(intval($c['ID_carrera']));
        ?>
            <div class="mb-4">
                <h3><?= htmlspecialchars($c['nombre']) ?></h3>
                <?php if (empty($materiasPor)): ?>
                    <p>No hay materias para esta carrera.</p>
                <?php else: ?>
                    <ul>
                    <?php foreach ($materiasPor as $mp):
                        $doc = $admin->getDocenteAsignado(intval($mp['ID_materia']), intval($c['ID_carrera']));
                    ?>
                        <li>
                            <?= htmlspecialchars($mp['nombre'] ?? ('#' . intval($mp['ID_materia']))) ?>
                            <?php if ($doc): ?>
                                — Profesor: <?= htmlspecialchars($doc['nombre'] . ' ' . $doc['apellido']) ?> (<?= htmlspecialchars($doc['email']) ?>)
                            <?php else: ?>
                                — <em>Sin docente asignado</em>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
    <script>
        // Mostrar select de carrera solo si se selecciona rol 'alumno'
        (function(){
            const rol = document.getElementById('rolSelect');
            const labelCarrera = document.getElementById('labelCarrera');
            function update(){
                if (rol.value === 'alumno') labelCarrera.style.display = 'block'; else labelCarrera.style.display = 'none';
            }
            rol.addEventListener('change', update);
            update();
        })();
    </script>

    <form method="post" action="admin.php">
        <h2>Crear carrera</h2>
        <input type="text" name="nombre_carrera" placeholder="Nombre de la carrera" required>
        <button type="submit" name="accion" value="crear_carrera">Crear carrera</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Crear materia</h2>
        <input type="text" name="nombre_materia" placeholder="Nombre de la materia" required>
        <label>Asignar a carrera:</label><br>
        <select name="carreras[]" multiple size="4" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <br>
        <label>Asignar docente (obligatorio)</label>
        <select name="ID_docente_materia" required>
            <option value="">-- seleccionar docente --</option>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= htmlspecialchars($d['ID_usuario']) ?>"><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?> (<?= htmlspecialchars($d['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <br>
        <button type="submit" name="accion" value="crear_materia">Crear materia</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Asignar docente a materia (por carrera)</h2>
        <label>Materia:</label>
        <select name="ID_materia" required>
            <?php foreach ($materias as $m): ?>
                <option value="<?= htmlspecialchars($m['ID_materia']) ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Carrera:</label>
        <select name="ID_carrera" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Docente:</label>
        <select name="ID_docente" required>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= htmlspecialchars($d['ID_usuario']) ?>"><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?> (<?= htmlspecialchars($d['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="accion" value="asignar_docente">Asignar docente</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Quitar docente de materia</h2>
        <label>Materia:</label>
        <select name="ID_materia" required>
            <?php foreach ($materias as $m): ?>
                <option value="<?= htmlspecialchars($m['ID_materia']) ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Carrera:</label>
        <select name="ID_carrera" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Docente:</label>
        <select name="ID_docente_remove" required>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= htmlspecialchars($d['ID_usuario']) ?>"><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?> (<?= htmlspecialchars($d['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="accion" value="quitar_docente">Quitar docente</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Eliminar materia</h2>
        <label>Materia:</label>
        <select name="ID_materia_remove" required>
            <?php foreach ($materias as $m): ?>
                <option value="<?= htmlspecialchars($m['ID_materia']) ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="accion" value="eliminar_materia" onclick="return confirm('¿Eliminar la materia y todas sus relaciones?')">Eliminar materia</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Desvincular materia de una carrera</h2>
        <label>Materia:</label>
        <select name="ID_materia_desvinc" required>
            <?php foreach ($materias as $m): ?>
                <option value="<?= htmlspecialchars($m['ID_materia']) ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Carrera:</label>
        <select name="ID_carrera_desvinc" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="accion" value="desvincular_materia" onclick="return confirm('¿Desvincular la materia de la carrera seleccionada?')">Desvincular</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Inscribir / Dar de baja alumno</h2>
        <label>Materia:</label>
        <select name="ID_materia" required>
            <?php foreach ($materias as $m): ?>
                <option value="<?= htmlspecialchars($m['ID_materia']) ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Carrera:</label>
        <select name="ID_carrera" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= htmlspecialchars($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Alumno:</label>
        <select name="ID_alumno" required>
            <?php foreach ($alumnos as $a): ?>
                <option value="<?= htmlspecialchars($a['ID_usuario']) ?>"><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?> (<?= htmlspecialchars($a['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="accion" value="inscribir_alumno">Inscribir</button>
        <button type="submit" name="accion" value="dar_baja_alumno">Dar de baja</button>
    </form>

    <form method="post" action="admin.php">
        <h2>Eliminar usuario</h2>
        <label>Usuario:</label>
        <select name="ID_usuario" required>
            <optgroup label="Alumnos">
            <?php foreach ($alumnos as $a): ?>
                <option value="<?= htmlspecialchars($a['ID_usuario']) ?>"><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?> (<?= htmlspecialchars($a['email']) ?>)</option>
            <?php endforeach; ?>
            </optgroup>
            <optgroup label="Docentes">
            <?php foreach ($docentes as $d): ?>
                <option value="<?= htmlspecialchars($d['ID_usuario']) ?>"><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?> (<?= htmlspecialchars($d['email']) ?>)</option>
            <?php endforeach; ?>
            </optgroup>
        </select>
        <button type="submit" name="accion" value="eliminar_usuario" onclick="return confirm('¿Eliminar usuario seleccionado? Esto no se puede deshacer.')">Eliminar usuario</button>
    </form>
</body>
</html>