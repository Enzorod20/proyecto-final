<?php
session_start();
if (!isset($_SESSION['email']) || strtolower($_SESSION['rol'] ?? '') !== 'alumno') {
    header('Location: login_unificado.php');
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
    echo 'Alumno no encontrado';
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();
$alumnoId = intval($row['ID_usuario']);

$msg = '';

$conn->query("CREATE TABLE IF NOT EXISTS carrera (
    ID_carrera INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


$conn->query("CREATE TABLE IF NOT EXISTS usuario_carrera (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_usuario INT NOT NULL,
    ID_carrera INT NOT NULL,
    UNIQUE KEY uk_usuario_carrera (ID_usuario, ID_carrera),
    FOREIGN KEY (ID_usuario) REFERENCES usuario(ID_usuario) ON DELETE CASCADE,
    FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'inscribir_carrera') {
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        if ($ID_carrera <= 0) {
            $msg = 'Carrera inválida';
        } else {
            $st = $conn->prepare("INSERT IGNORE INTO usuario_carrera (ID_usuario, ID_carrera) VALUES (?, ?)");
            if ($st) {
                $st->bind_param('ii', $alumnoId, $ID_carrera);
                if ($st->execute()) {
                    $msg = 'Inscripción en la carrera realizada';
                } else {
                    $msg = 'Error al inscribirse: ' . $st->error;
                }
                $st->close();
            } else {
                $msg = 'Error DB: ' . $conn->error;
            }
        }
    } elseif ($action === 'inscribir_materia') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        if ($ID_materia <= 0 || $ID_carrera <= 0) {
            $msg = 'Materia o carrera inválida';
        } else {
            $chk = $conn->prepare("SELECT 1 FROM usuario_carrera WHERE ID_usuario = ? AND ID_carrera = ? LIMIT 1");
            $chk->bind_param('ii', $alumnoId, $ID_carrera);
            $chk->execute();
            $rchk = $chk->get_result();
            $enCarrera = ($rchk && $rchk->num_rows > 0);
            $chk->close();
            if (!$enCarrera) {
                $msg = 'Debes inscribirte en la carrera antes de inscribirte en materias de la misma.';
            } else {
                $chkMat = $conn->prepare("SELECT 1 FROM materia WHERE ID_materia = ? AND ID_carrera = ? LIMIT 1");
                $chkMat->bind_param('ii', $ID_materia, $ID_carrera);
                $chkMat->execute();
                $rmc1 = $chkMat->get_result();
                $found = ($rmc1 && $rmc1->num_rows > 0);
                $chkMat->close();
                if (!$found) {
                    $chkMat2 = $conn->prepare("SELECT 1 FROM materia_carrera WHERE ID_materia = ? AND ID_carrera = ? LIMIT 1");
                    $chkMat2->bind_param('ii', $ID_materia, $ID_carrera);
                    $chkMat2->execute();
                    $rmc2 = $chkMat2->get_result();
                    $found = ($rmc2 && $rmc2->num_rows > 0);
                    $chkMat2->close();
                }
                if (!$found) {
                    $msg = 'La materia seleccionada no pertenece a la carrera elegida.';
                } else {
                $ins = $conn->prepare("INSERT IGNORE INTO materia_alumno (ID_materia, ID_carrera, ID_alumno) VALUES (?, ?, ?)");
                if ($ins) {
                    $ins->bind_param('iii', $ID_materia, $ID_carrera, $alumnoId);
                    if ($ins->execute()) {
                        $msg = 'Inscripción en materia realizada';
                    } else {
                        $msg = 'Error al inscribirse en materia: ' . $ins->error;
                    }
                    $ins->close();
                } else {
                    $msg = 'Error DB: ' . $conn->error;
                }
                }
            }
        }
    } elseif ($action === 'dar_baja_materia') {
        $ID_materia = intval($_POST['ID_materia'] ?? 0);
        $ID_carrera = intval($_POST['ID_carrera'] ?? 0);
        if ($ID_materia <= 0 || $ID_carrera <= 0) {
            $msg = 'Datos inválidos';
        } else {
            $del = $conn->prepare("DELETE FROM materia_alumno WHERE ID_materia = ? AND ID_carrera = ? AND ID_alumno = ?");
            if ($del) {
                $del->bind_param('iii', $ID_materia, $ID_carrera, $alumnoId);
                if ($del->execute()) {
                    $msg = 'Baja de materia realizada';
                } else {
                    $msg = 'Error al dar de baja: ' . $del->error;
                }
                $del->close();
            } else {
                $msg = 'Error DB: ' . $conn->error;
            }
        }
    }
}

$carName = \Clases\Admin::detectNameColumn('carrera') ?? 'nombre';
$carreras = [];
$res = $conn->query("SELECT ID_carrera, `" . $conn->real_escape_string($carName) . "` AS nombre FROM carrera ORDER BY `" . $conn->real_escape_string($carName) . "`");
if ($res) while ($r = $res->fetch_assoc()) $carreras[] = $r;

$misCarreras = [];
$stc = $conn->prepare("SELECT uc.ID_carrera, c.`" . $conn->real_escape_string($carName) . "` AS nombre FROM usuario_carrera uc JOIN carrera c ON uc.ID_carrera = c.ID_carrera WHERE uc.ID_usuario = ? ORDER BY c.`" . $conn->real_escape_string($carName) . "`");
$stc->bind_param('i', $alumnoId);
$stc->execute();
$rc = $stc->get_result();
if ($rc) while ($r = $rc->fetch_assoc()) $misCarreras[] = $r;
$stc->close();

$materiasPorCarrera = [];
$matName = \Clases\Admin::detectNameColumn('materia') ?? 'nombre';
$sm = $conn->prepare("SELECT m.ID_materia, m.ID_carrera, m.`" . $conn->real_escape_string($matName) . "` AS nombre FROM materia m JOIN materia_carrera mc ON m.ID_materia = mc.ID_materia AND mc.ID_carrera = m.ID_carrera WHERE m.ID_carrera = ? OR mc.ID_carrera = ? GROUP BY m.ID_materia");
$misMaterias = [];

$q = "SELECT ma.ID_materia, ma.ID_carrera, m.`" . $conn->real_escape_string($matName) . "` AS materia_nombre, c.`" . $conn->real_escape_string($carName) . "` AS carrera_nombre, ma.nota1, ma.nota2
      FROM materia_alumno ma
      JOIN materia m ON ma.ID_materia = m.ID_materia
      JOIN carrera c ON ma.ID_carrera = c.ID_carrera
    WHERE ma.ID_alumno = ? ORDER BY c.`" . $conn->real_escape_string($carName) . "`, m.`" . $conn->real_escape_string($matName) . "`";
$stm = $conn->prepare($q);
$stm->bind_param('i', $alumnoId);
$stm->execute();
$rm = $stm->get_result();
if ($rm) while ($r = $rm->fetch_assoc()) $misMaterias[] = $r;
$stm->close();

function statusLabel($s) {
    switch ($s) {
        case 'promocionado': return 'Promocionado';
        case 'libre': return 'Libre';
        case 'regular': return 'Regular';
        case 'sin_notas': return 'Sin notas';
        default: return $s;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../design/head.php'; ?>
    <title>Panel alumno - Inscripciones y notas</title>
</head>
<body class="container">
    <header style="display:flex;justify-content:space-between;align-items:center">
        <div>Conectado como: <?= htmlspecialchars($_SESSION['email']) ?> — Rol: alumno</div>
        <form action="logout.php" method="post" style="margin:0">
            <button type="submit">Cerrar sesión</button>
        </form>
    </header>

    <h1>Inscripciones y notas</h1>
    <?php if ($msg): ?>
        <p><strong><?= htmlspecialchars($msg) ?></strong></p>
    <?php endif; ?>

    <h2> Inscribirme en una carrera</h2>
    <p>Inscripto en:</p>
    <?php if (empty($misCarreras)): ?>
        <p>No estas inscripto en ninguna carrera.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($misCarreras as $c): ?>
                <li><?= htmlspecialchars($c['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    $enrolledIds = array_column($misCarreras, 'ID_carrera');
    $availableCarreras = [];
    foreach ($carreras as $c) {
        if (!in_array($c['ID_carrera'], $enrolledIds, true)) $availableCarreras[] = $c;
    }
    ?>
    <?php if (empty($availableCarreras)): ?>
        <p>Ya estas inscrito en todas las carreras disponibles.</p>
    <?php else: ?>
        <form method="post" action="alumno_panel.php">
            <input type="hidden" name="action" value="inscribir_carrera">
            <label>Elegir carrera:
                <select name="ID_carrera" required>
                    <option value="">-- seleccionar --</option>
                    <?php foreach ($availableCarreras as $c): ?>
                        <option value="<?= intval($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Inscribirme</button>
        </form>
    <?php endif; ?>

    <h2> Inscripcion a materias</h2>
    <?php if (empty($misCarreras)): ?>
        <p>Inscribete primero en una carrera para poder anotarte en materias.</p>
    <?php else: ?>
        <form method="post" action="alumno_panel.php">
            <input type="hidden" name="action" value="inscribir_materia">
            <label>Carrera:
                <select name="ID_carrera" required onchange="this.form.ID_materia.length=0;">
                    <?php foreach ($misCarreras as $c): ?>
                        <option value="<?= intval($c['ID_carrera']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Materia:
                <select name="ID_materia" required>
                    <option value="">Selecciona la carrera primero</option>
                    <?php
                    $mq = $conn->query("SELECT m.ID_materia, m.ID_carrera, m.`" . $conn->real_escape_string($matName) . "` AS nombre FROM materia m ORDER BY m.`" . $conn->real_escape_string($matName) . "`");
                    if ($mq) {
                        while ($mm = $mq->fetch_assoc()) {
                            echo '<option data-carrera="' . intval($mm['ID_carrera']) . '" value="' . intval($mm['ID_materia']) . '">' . htmlspecialchars($mm['nombre']) . ' (' . intval($mm['ID_carrera']) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </label>
            <button type="submit">Inscribirme en la materia</button>
        </form>
        <p style="font-size:small;color:gray">Nota: si la materia pertenece a otra carrera distinta a la seleccionada, la inscripción será rechazada.</p>
    <?php endif; ?>

    <h2>Mis materias y notas</h2>
    <?php if (empty($misMaterias)): ?>
        <p>No estas inscripto en ninguna materia todavia.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Materia</th><th>Carrera</th><th>Nota 1</th><th>Nota 2</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($misMaterias as $mm):
                $s = \Clases\Docente::computeStatus($mm['nota1'], $mm['nota2']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($mm['materia_nombre']) ?></td>
                    <td><?= htmlspecialchars($mm['carrera_nombre']) ?></td>
                    <td><?= $mm['nota1'] === null ? '-' : htmlspecialchars($mm['nota1']) ?></td>
                    <td><?= $mm['nota2'] === null ? '-' : htmlspecialchars($mm['nota2']) ?></td>
                    <td><?= htmlspecialchars(statusLabel($s)) ?></td>
                    <td>
                        <form method="post" action="alumno_panel.php" class="inline">
                            <input type="hidden" name="action" value="dar_baja_materia">
                            <input type="hidden" name="ID_materia" value="<?= intval($mm['ID_materia']) ?>">
                            <input type="hidden" name="ID_carrera" value="<?= intval($mm['ID_carrera']) ?>">
                            <button type="submit">Darme de baja</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php">Volver al panel</a></p>
</body>
</html>
<script>

document.addEventListener('DOMContentLoaded', function(){
    var carreraSelect = document.querySelector('select[name="ID_carrera"]');
    var materiaSelect = document.querySelector('select[name="ID_materia"]');
    if (!carreraSelect || !materiaSelect) return;
    function filterOptions(){
        var carrera = carreraSelect.value;
        for (var i=0;i<materiaSelect.options.length;i++){
            var opt = materiaSelect.options[i];
            var data = opt.getAttribute('data-carrera');
            if (!data) { opt.style.display = ''; continue; }
            if (carrera === '' || data === carrera) opt.style.display = '';
            else opt.style.display = 'none';
        }
    }
    carreraSelect.addEventListener('change', filterOptions);
    filterOptions();
});
</script>
