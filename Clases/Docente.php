<?php
namespace Clases;
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/Admin.php';

class Docente extends Usuario
{
    public function renderDashboard(): string
    {
        $name = htmlspecialchars($this->getName());
        return "
        <section>
            <h1>Panel docente</h1>
            <p>Bienvenido, {$name} (Docente)</p>
        </section>
        HTML";
    }

  
    public function getAssignedMaterias(): array
    {
        $conn = \Database::getConnection();
        $matName = \Clases\Admin::detectNameColumn('materia') ?? 'ID_materia';
        $carName = \Clases\Admin::detectNameColumn('carrera') ?? 'ID_carrera';
        $sql = "SELECT md.ID, md.ID_materia, md.ID_carrera, m.`" . $conn->real_escape_string($matName) . "` AS materia_nombre, c.`" . $conn->real_escape_string($carName) . "` AS carrera_nombre
            FROM materia_docente md
            JOIN materia m ON md.ID_materia = m.ID_materia
            JOIN carrera c ON md.ID_carrera = c.ID_carrera
            WHERE md.ID_docente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }
        $stmt->close();
        return $out;
    }

    public function inscribirAlumno(int $ID_materia, int $ID_carrera, int $ID_alumno): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM materia_docente WHERE ID_materia = ? AND ID_carrera = ? AND ID_docente = ? LIMIT 1");
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $this->id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        if (!$ok) {
            return ['success' => false, 'message' => 'No autorizado: no estás asignado a esa materia/carrera'];
        }
        $stmt2 = $conn->prepare("INSERT IGNORE INTO materia_alumno (ID_materia, ID_carrera, ID_alumno) VALUES (?, ?, ?)");
        $stmt2->bind_param('iii', $ID_materia, $ID_carrera, $ID_alumno);
        if (!$stmt2->execute()) {
            $err = $stmt2->error;
            $stmt2->close();
            return ['success' => false, 'message' => 'Error al inscribir alumno: ' . $err];
        }
        $stmt2->close();
        return ['success' => true, 'message' => 'Alumno inscrito'];
    }

    public function darDeBajaAlumno(int $ID_materia, int $ID_carrera, int $ID_alumno): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM materia_docente WHERE ID_materia = ? AND ID_carrera = ? AND ID_docente = ? LIMIT 1");
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $this->id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        if (!$ok) {
            return ['success' => false, 'message' => 'No autorizado: no estás asignado a esa materia/carrera'];
        }
        $stmt2 = $conn->prepare("DELETE FROM materia_alumno WHERE ID_materia = ? AND ID_carrera = ? AND ID_alumno = ?");
        $stmt2->bind_param('iii', $ID_materia, $ID_carrera, $ID_alumno);
        if (!$stmt2->execute()) {
            $err = $stmt2->error;
            $stmt2->close();
            return ['success' => false, 'message' => 'Error al dar de baja alumno: ' . $err];
        }
        $stmt2->close();
        return ['success' => true, 'message' => 'Alumno dado de baja'];
    }


    public function setNotas(int $ID_materia, int $ID_carrera, int $ID_alumno, $nota1 = null, $nota2 = null): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM materia_docente WHERE ID_materia = ? AND ID_carrera = ? AND ID_docente = ? LIMIT 1");
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $this->id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        if (!$ok) {
            return ['success' => false, 'message' => 'No autorizado: no estás asignado a esa materia/carrera'];
        }
        $ch = $conn->prepare("SELECT 1 FROM materia_alumno WHERE ID_materia = ? AND ID_carrera = ? AND ID_alumno = ? LIMIT 1");
        $ch->bind_param('iii', $ID_materia, $ID_carrera, $ID_alumno);
        $ch->execute();
        $rch = $ch->get_result();
        $exists = ($rch && $rch->num_rows > 0);
        $ch->close();
        if (!$exists) {
            return ['success' => false, 'message' => 'El alumno no está inscripto en esta materia/carrera'];
        }

        $stmt = $conn->prepare("UPDATE materia_alumno SET nota1 = ?, nota2 = ? WHERE ID_materia = ? AND ID_carrera = ? AND ID_alumno = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];

        $v1 = is_null($nota1) ? null : (string)($nota1);
        $v2 = is_null($nota2) ? null : (string)($nota2);

        $stmt->bind_param('ssiii', $v1, $v2, $ID_materia, $ID_carrera, $ID_alumno);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al guardar notas: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Notas actualizadas'];
    }

    public static function computeStatus($nota1, $nota2): string
    {
        if ($nota1 === null && $nota2 === null) return 'sin_notas';
        $n1 = is_null($nota1) ? null : floatval($nota1);
        $n2 = is_null($nota2) ? null : floatval($nota2);
        if ($n1 !== null && $n2 !== null) {
            if ($n1 >= 7 && $n2 >= 7) return 'promocionado';
            if ($n1 < 6 || $n2 < 6) return 'libre';
            if ($n1 == 6 || $n2 == 6) return 'regular';
            return 'regular';
        }
        if ($n1 === null || $n2 === null) {
            $present = $n1 === null ? $n2 : $n1;
            if ($present < 6) return 'libre';
            if ($present > 7) return 'promocionado';
            if ($present == 6) return 'regular';
            return 'regular';
        }
        return 'regular';
    }
}
?>