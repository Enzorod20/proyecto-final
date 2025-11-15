<?php
session_start();
if (!isset($_SESSION['email']) || strtolower($_SESSION['rol'] ?? '') !== 'docente') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../Clases/Docente.php';

$conn = \Database::getConnection();
$stmt = $conn->prepare("SELECT u.*, r.Rol_nombre FROM usuario u LEFT JOIN rol r ON u.ID_rol = r.ID_rol WHERE u.email = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo 'Docente no encontrado';
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();
$doc = new \Clases\Docente($row);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'inscribir') {
        $ID_materia = $_POST['ID_materia'] ?? 0;
        $ID_carrera = $_POST['ID_carrera'] ?? 0;
        $ID_alumno = $_POST['ID_alumno'] ?? 0;
        $r = $doc->inscribirAlumno($ID_materia, $ID_carrera, $ID_alumno);
        $msg = htmlspecialchars($r['message']);
    } elseif ($action === 'dar_baja') {
        $ID_materia = $_POST['ID_materia'] ?? 0;
        $ID_carrera = $_POST['ID_carrera'] ?? 0;
        $ID_alumno = $_POST['ID_alumno'] ?? 0;
        $r = $doc->darDeBajaAlumno($ID_materia, $ID_carrera, $ID_alumno);
        $msg = htmlspecialchars($r['message']);
    } elseif ($action === 'set_notas') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        $ID_alumno = intval($_POST['ID_alumno'] ?? 0);
        $nota1 = isset($_POST['nota1']) && $_POST['nota1'] !== '' ? floatval($_POST['nota1']) : null;
        $nota2 = isset($_POST['nota2']) && $_POST['nota2'] !== '' ? floatval($_POST['nota2']) : null;
        $r = $doc->setNotas($ID_materia, $ID_carrera, $ID_alumno, $nota1, $nota2);
        $msg = htmlspecialchars($r['message']);
    }
}

$materias = $doc->getAssignedMaterias();

$students = [];
$stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.email FROM usuario u JOIN rol r ON u.ID_rol = r.ID_rol WHERE LOWER(r.Rol_nombre) = 'alumno' ORDER BY u.nombre");
$stmt->execute();
$res2 = $stmt->get_result();
if ($res2) {
    while ($r2 = $res2->fetch_assoc()) $students[] = $r2;
}
$stmt->close();


$filter = $_GET['filter'] ?? 'all';

function computeStatus($n1, $n2) {
    return \Clases\Docente::computeStatus($n1, $n2);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Panel docente - Mis materias</title>
</head>
<body class="container">
    <header style="display:flex;justify-content:space-between;align-items:center">
        <div>Docente: <?= htmlspecialchars($doc->getName()) ?> — <a href="index.php">Volver</a></div>
        <form action="logout.php" method="post" style="margin:0"><button type="submit">Cerrar sesión</button></form>
    </header>

    <h1>Mis materias</h1>
    <?php if ($msg): ?>
        <p><strong><?= $msg ?></strong></p>
    <?php endif; ?>

    <p>Filtrar alumnos por estado: <a href="?filter=all">Todos</a> | <a href="?filter=promocionado">Promocionados</a> | <a href="?filter=regular">Regulares</a> | <a href="?filter=libre">Libres</a></p>

    <?php if (empty($materias)): ?>
        <p>No tienes materias asignadas.</p>
    <?php else: ?>
        <?php foreach ($materias as $m):
            $ID_materia = intval($m['ID_materia']);
            $ID_carrera = intval($m['ID_carrera']);
            $matName = htmlspecialchars($m['materia_nombre'] ?? ('#' . $ID_materia));
            $carName = htmlspecialchars($m['carrera_nombre'] ?? ('#' . $ID_carrera));

            $stmt = $conn->prepare("SELECT ma.ID_materia, ma.ID_carrera, ma.ID_alumno, u.nombre, u.apellido, u.email, ma.nota1, ma.nota2
                FROM materia_alumno ma
                JOIN usuario u ON ma.ID_alumno = u.ID_usuario
                WHERE ma.ID_materia = ? AND ma.ID_carrera = ? ORDER BY u.nombre");
            $stmt->bind_param('ii', $ID_materia, $ID_carrera);
            $stmt->execute();
            $resA = $stmt->get_result();
            $alumnos = [];
            if ($resA) {
                while ($ra = $resA->fetch_assoc()) $alumnos[] = $ra;
            }
            $stmt->close();
        ?>
            <section style="margin-bottom:30px">
                <h2><?= $matName ?> — <?= $carName ?></h2>

                <h3>Alumnos inscriptos</h3>
                <?php if (empty($alumnos)): ?>
                    <p>No hay alumnos inscriptos.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Alumno</th><th>Email</th><th>1ª nota</th><th>2ª nota</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($alumnos as $a):
                            $status = computeStatus($a['nota1'], $a['nota2']);
                            if ($filter !== 'all' && $status !== $filter) continue;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido']) ?></td>
                                <td><?= htmlspecialchars($a['email']) ?></td>
                                <td><?= is_null($a['nota1']) ? '-' : htmlspecialchars($a['nota1']) ?></td>
                                <td><?= is_null($a['nota2']) ? '-' : htmlspecialchars($a['nota2']) ?></td>
                                <td><?= htmlspecialchars($status) ?></td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <form method="post" action="" class="inline-block">
                                            <input type="hidden" name="action" value="dar_baja">
                                            <input type="hidden" name="ID_materia" value="<?= $ID_materia ?>">
                                            <input type="hidden" name="ID_carrera" value="<?= $ID_carrera ?>">
                                            <input type="hidden" name="ID_alumno" value="<?= intval($a['ID_alumno']) ?>">
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">Quitar inscripción</button>
                                        </form>

                                        <form method="post" action="" class="inline-flex items-center space-x-2 bg-gray-50 p-2 rounded">
                                            <input type="hidden" name="action" value="set_notas">
                                            <input type="hidden" name="ID_materia" value="<?= $ID_materia ?>">
                                            <input type="hidden" name="ID_carrera" value="<?= $ID_carrera ?>">
                                            <input type="hidden" name="ID_alumno" value="<?= intval($a['ID_alumno']) ?>">
                                            <input type="number" name="nota1" placeholder="1ª nota" class="w-16 p-1 border rounded" value="<?= htmlspecialchars($a['nota1'] ?? '') ?>" max ="10"; min="1";>
                                            <input type="number" name="nota2" placeholder="2ª nota" class="w-16 p-1 border rounded" value="<?= htmlspecialchars($a['nota2'] ?? '') ?>" max ="10"; min="1";>
                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">Guardar calificaciones</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h3>Inscribir alumno</h3>
                <form method="post" action="">
                    <input type="hidden" name="action" value="inscribir">
                    <input type="hidden" name="ID_materia" value="<?= $ID_materia ?>">
                    <input type="hidden" name="ID_carrera" value="<?= $ID_carrera ?>">
                    <select name="ID_alumno" required>
                        <option value=""> seleccionar alumno </option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= intval($s['ID_usuario']) ?>"><?= htmlspecialchars($s['nombre'] . ' ' . $s['apellido'] . ' (' . $s['email'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded">Inscribir alumno</button>
                </form>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
