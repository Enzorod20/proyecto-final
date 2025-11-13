<?php
namespace Clases;
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/Usuario.php';

class Admin extends Usuario
{
    public function __construct($data)
    {
        if (is_array($data)) {
            parent::__construct($data);
            return;
        }

        if (is_string($data)) {
            $conn = \Database::getConnection();
            $stmt = $conn->prepare("SELECT u.*, r.Rol_nombre FROM usuario u LEFT JOIN rol r ON u.ID_rol = r.ID_rol WHERE u.email = ? LIMIT 1");
            $stmt->bind_param('s', $data);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if (!$row) {
                throw new \Exception('Usuario no encontrado: ' . $data);
            }
            parent::__construct($row);
            return;
        }

        throw new \TypeError('Admin::__construct espera array o email (string)');
    }

    public function renderDashboard(): string
    {
        $name = htmlspecialchars($this->getName());
        return "
        <section>
            <h1>Panel administrador</h1>
            <p>Bienvenido, {$name} (Admin)</p>
            <ul>
                <li><a href='admin.php'>Panel de administración</a></li>
            </ul>
            <form action='logout.php' method='post' style='margin-top: 20px;'>
                <button type='submit'>Cerrar sesión</button>
            </form>
        </section>
        ";
    }

     function ensureSchema()
    {
        $conn = \Database::getConnection();
        $conn->query("CREATE TABLE IF NOT EXISTS carrera (
            ID_carrera INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS materia (
            ID_materia INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            ID_carrera INT NOT NULL,
            ID_docente INT,
            FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera),
            FOREIGN KEY (ID_docente) REFERENCES usuario(ID_usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS materia_carrera (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_materia INT NOT NULL,
            ID_carrera INT NOT NULL,
            UNIQUE KEY uk_materia_carrera (ID_materia, ID_carrera),
            FOREIGN KEY (ID_materia) REFERENCES materia(ID_materia) ON DELETE CASCADE,
            FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS materia_docente (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_materia INT NOT NULL,
            ID_carrera INT NOT NULL,
            ID_docente INT NOT NULL,
            UNIQUE KEY uk_materia_carrera_docente (ID_materia, ID_carrera, ID_docente),
            FOREIGN KEY (ID_materia) REFERENCES materia(ID_materia) ON DELETE CASCADE,
            FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera) ON DELETE CASCADE,
            FOREIGN KEY (ID_docente) REFERENCES usuario(ID_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS materia_alumno (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_materia INT NOT NULL,
            ID_carrera INT NOT NULL,
            ID_alumno INT NOT NULL,
            nota1 DECIMAL(5,2) DEFAULT NULL,
            nota2 DECIMAL(5,2) DEFAULT NULL,
            UNIQUE KEY uk_materia_carrera_alumno (ID_materia, ID_carrera, ID_alumno),
            FOREIGN KEY (ID_materia) REFERENCES materia(ID_materia) ON DELETE CASCADE,
            FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera) ON DELETE CASCADE,
            FOREIGN KEY (ID_alumno) REFERENCES usuario(ID_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $resCheck = $conn->query("SHOW COLUMNS FROM materia_alumno LIKE 'nota1'");
        if (!($resCheck && $resCheck->num_rows > 0)) {
            $conn->query("ALTER TABLE materia_alumno ADD COLUMN nota1 DECIMAL(5,2) DEFAULT NULL");
        }
        $resCheck = $conn->query("SHOW COLUMNS FROM materia_alumno LIKE 'nota2'");
        if (!($resCheck && $resCheck->num_rows > 0)) {
            $conn->query("ALTER TABLE materia_alumno ADD COLUMN nota2 DECIMAL(5,2) DEFAULT NULL");
        }
        $conn->query("CREATE TABLE IF NOT EXISTS usuario_carrera (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_usuario INT NOT NULL,
            ID_carrera INT NOT NULL,
            UNIQUE KEY uk_usuario_carrera (ID_usuario, ID_carrera),
            FOREIGN KEY (ID_usuario) REFERENCES usuario(ID_usuario) ON DELETE CASCADE,
            FOREIGN KEY (ID_carrera) REFERENCES carrera(ID_carrera) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function detectNameColumn(string $table, array $candidates = null)
    {
        $candidates = $candidates ?? ['nombre','Nombre','nombre_carrera','Carrera_nombre','carrera_nombre','nombre_materia'];
        $conn = \Database::getConnection();
        $tableEsc = mysqli_real_escape_string($conn, $table);
        $res = $conn->query("SHOW COLUMNS FROM `{$tableEsc}`");
        if (!$res) return null;
        $cols = [];
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
        foreach ($candidates as $cand) {
            if (in_array($cand, $cols, true)) return $cand;
        }
        $res->data_seek(0);
        foreach ($cols as $col) {
            $r = $conn->query("SHOW FULL COLUMNS FROM `{$tableEsc}` LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
            if ($r) {
                $row = $r->fetch_assoc();
                if (isset($row['Type']) && preg_match('/varchar|text|char/i', $row['Type'])) {
                    return $col;
                }
            }
        }
        return null;
    }

    public static function getReferencedTable(string $table, string $column)
    {
        $conn = \Database::getConnection();
        $db = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'] ?? null;
        if (!$db) return null;
        $tableEsc = mysqli_real_escape_string($conn, $table);
        $columnEsc = mysqli_real_escape_string($conn, $column);
        $sql = "SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'
              AND TABLE_NAME = '" . $tableEsc . "'
              AND COLUMN_NAME = '" . $columnEsc . "' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return $row['REFERENCED_TABLE_NAME'];
        }
        return null;
    }

    public function crearCarrera(string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['success' => false, 'message' => 'Nombre de carrera vacío'];
        }
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $nameCol = self::detectNameColumn('carrera') ?? 'nombre';
        $colEsc = $conn->real_escape_string($nameCol);
        $sql = "INSERT INTO carrera (`" . $colEsc . "`) VALUES (?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error en DB: ' . $conn->error];
        }
        $stmt->bind_param('s', $nombre);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al crear carrera: ' . $err];
        }
        $id = intval($conn->insert_id);
        $stmt->close();
        return ['success' => true, 'message' => 'Carrera creada', 'ID_carrera' => $id];
    }

    public function listarCarreras(): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $nameCol = self::detectNameColumn('carrera');
        if ($nameCol) {
            $sql = "SELECT ID_carrera, `" . $conn->real_escape_string($nameCol) . "` AS nombre FROM carrera ORDER BY `" . $conn->real_escape_string($nameCol) . "`";
        } else {
            $sql = "SELECT ID_carrera FROM carrera";
        }
        $res = $conn->query($sql);
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }
        return $out;
    }

    public function crearMateria(string $nombre, array $carreras, int $ID_docente = null): array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || empty($carreras)) {
            return ['success' => false, 'message' => 'Nombre o carreras inválidas'];
        }
        $ID_carrera = intval($carreras[0]); 
        if ($ID_carrera <= 0) {
            return ['success' => false, 'message' => 'Se requiere al menos una carrera válida'];
        }

        $this->ensureSchema();
        $conn = \Database::getConnection();
        if (!is_null($ID_docente)) {
            $ID_docente = intval($ID_docente);
            if ($ID_docente <= 0) $ID_docente = null;
        }
        $nameCol = self::detectNameColumn('materia') ?? 'nombre';
        $colEsc = $conn->real_escape_string($nameCol);
        $hasDocente = false;
        $docenteNotNull = false;
        $resCols = $conn->query("SHOW COLUMNS FROM `materia` LIKE 'ID_docente'");
        if ($resCols && $resCols->num_rows > 0) {
            $hasDocente = true;
            $colInfo = $resCols->fetch_assoc();
            $docenteNotNull = (isset($colInfo['Null']) && strtoupper($colInfo['Null']) === 'NO');
        }

        if ($hasDocente && $docenteNotNull && is_null($ID_docente)) {
            return ['success' => false, 'message' => 'La tabla materia requiere un docente (ID_docente NOT NULL). Asigna un docente al crear la materia o modifica la estructura de la tabla.'];
        }

        if ($hasDocente && !is_null($ID_docente)) {
            $refTable = self::getReferencedTable('materia', 'ID_docente');
            $finalDocenteId = null;
            if ($refTable && strtolower($refTable) !== 'usuario') {
                $resCols = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($refTable) . "` LIKE 'ID_usuario'");
                if ($resCols && $resCols->num_rows > 0) {
                    $stmtMap = $conn->prepare("SELECT ID_" . $conn->real_escape_string($refTable) . " AS id FROM `" . $conn->real_escape_string($refTable) . "` WHERE ID_usuario = ? LIMIT 1");
                    if ($stmtMap) {
                        $stmtMap->bind_param('i', $ID_docente);
                        $stmtMap->execute();
                        $rmap = $stmtMap->get_result();
                        if ($rmap && $rmap->num_rows > 0) {
                            $rowmap = $rmap->fetch_assoc();
                            $finalDocenteId = current($rowmap);
                        }
                        $stmtMap->close();
                    }
                }

                if (is_null($finalDocenteId)) {
                    $stmtChk = $conn->prepare("SELECT 1 FROM `" . $conn->real_escape_string($refTable) . "` WHERE ID_" . $conn->real_escape_string($refTable) . " = ? LIMIT 1");
                    if ($stmtChk) {
                        $stmtChk->bind_param('i', $ID_docente);
                        $stmtChk->execute();
                        $rchk = $stmtChk->get_result();
                        if ($rchk && $rchk->num_rows > 0) {
                            $finalDocenteId = $ID_docente;
                        }
                        $stmtChk->close();
                    }
                }

                if (is_null($finalDocenteId)) {
                    $resCols2 = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($refTable) . "` LIKE 'ID_usuario'");
                    if ($resCols2 && $resCols2->num_rows > 0) {
                        $ins = $conn->prepare("INSERT INTO `" . $conn->real_escape_string($refTable) . "` (ID_usuario) VALUES (?)");
                        if ($ins) {
                            $ins->bind_param('i', $ID_docente);
                            if ($ins->execute()) {
                                $finalDocenteId = intval($conn->insert_id);
                            }
                            $ins->close();
                        }
                    }
                }
            } else {
                $finalDocenteId = $ID_docente;
            }

            if (is_null($finalDocenteId)) {
                return ['success' => false, 'message' => 'No se pudo mapear el docente al esquema de la base (tabla referenciada a ID_docente).'];
            }

            $sql = "INSERT INTO materia (`" . $colEsc . "`, ID_carrera, ID_docente) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Error en DB: ' . $conn->error];
            }
            $stmt->bind_param('sii', $nombre, $ID_carrera, $finalDocenteId);
        } else {
            $sql = "INSERT INTO materia (`" . $colEsc . "`, ID_carrera) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Error en DB: ' . $conn->error];
            }
            $stmt->bind_param('si', $nombre, $ID_carrera);
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al crear materia: ' . $err];
        }
        $id_materia = intval($conn->insert_id);
        $stmt->close();

        $stmt2 = $conn->prepare("INSERT IGNORE INTO materia_carrera (ID_materia, ID_carrera) VALUES (?, ?)");
        foreach (array_slice($carreras, 1) as $idc) { 
            $idc = intval($idc);
            $stmt2->bind_param('ii', $id_materia, $idc);
            $stmt2->execute();
        }
        $stmt2->close();
        return ['success' => true, 'message' => 'Materia creada', 'ID_materia' => $id_materia];
    }

    public function listarMaterias(): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $nameCol = self::detectNameColumn('materia');
        if ($nameCol) {
            $sql = "SELECT m.ID_materia, m.`" . $conn->real_escape_string($nameCol) . "` AS nombre FROM materia m ORDER BY m.`" . $conn->real_escape_string($nameCol) . "`";
        } else {
            $sql = "SELECT m.ID_materia FROM materia m";
        }
        $res = $conn->query($sql);
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }
        return $out;
    }

    public function listarDocentes(): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.email FROM usuario u JOIN rol r ON u.ID_rol = r.ID_rol WHERE LOWER(r.Rol_nombre) = 'docente' ORDER BY u.nombre");
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

    public function listarAlumnos(): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.email FROM usuario u JOIN rol r ON u.ID_rol = r.ID_rol WHERE LOWER(r.Rol_nombre) = 'alumno' ORDER BY u.nombre");
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

    public function asignarDocenteAMateria(int $ID_materia, int $ID_carrera, int $ID_docente): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("INSERT IGNORE INTO materia_docente (ID_materia, ID_carrera, ID_docente) VALUES (?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        }
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $ID_docente);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al asignar docente: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Docente asignado'];
    }

    public function inscribirAlumno(int $ID_materia, int $ID_carrera, int $ID_alumno): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("INSERT IGNORE INTO materia_alumno (ID_materia, ID_carrera, ID_alumno) VALUES (?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        }
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $ID_alumno);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al inscribir alumno: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Alumno inscrito'];
    }

    public function darDeBajaAlumno(int $ID_materia, int $ID_carrera, int $ID_alumno): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM materia_alumno WHERE ID_materia = ? AND ID_carrera = ? AND ID_alumno = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        }
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $ID_alumno);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al dar de baja alumno: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Alumno dado de baja'];
    }

  
    public function eliminarMateria(int $ID_materia): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM materia WHERE ID_materia = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        $stmt->bind_param('i', $ID_materia);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al eliminar materia: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Materia eliminada'];
    }

    public function eliminarUsuario(int $ID_usuario): array
    {
        $conn = \Database::getConnection();
        if ($this->id && intval($this->id) === intval($ID_usuario)) {
            return ['success' => false, 'message' => 'No puede eliminar su propia cuenta mientras está logueado.'];
        }
        $stmt = $conn->prepare("DELETE FROM usuario WHERE ID_usuario = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        $stmt->bind_param('i', $ID_usuario);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al eliminar usuario: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Usuario eliminado'];
    }

    /**
     * Asignar un usuario a una carrera (usuario_carrera).
     */
    public function asignarUsuarioACarrera(int $ID_usuario, int $ID_carrera): array
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("INSERT IGNORE INTO usuario_carrera (ID_usuario, ID_carrera) VALUES (?, ?)");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        $stmt->bind_param('ii', $ID_usuario, $ID_carrera);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al asignar usuario a carrera: ' . $err];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Usuario asignado a la carrera'];
    }

    /**
     * Obtener docente asignado a una materia en una carrera (si lo hay).
     */
    public function getDocenteAsignado(int $ID_materia, int $ID_carrera)
    {
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("SELECT u.ID_usuario, u.nombre, u.apellido, u.email FROM materia_docente md JOIN usuario u ON md.ID_docente = u.ID_usuario WHERE md.ID_materia = ? AND md.ID_carrera = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('ii', $ID_materia, $ID_carrera);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    
    public function removerMateriaDeCarrera(int $ID_materia, int $ID_carrera): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM materia_carrera WHERE ID_materia = ? AND ID_carrera = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        $stmt->bind_param('ii', $ID_materia, $ID_carrera);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al desvincular materia de la carrera: ' . $err];
        }
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT ID_carrera FROM materia WHERE ID_materia = ? LIMIT 1");
        if ($stmt2) {
            $stmt2->bind_param('i', $ID_materia);
            $stmt2->execute();
            $r = $stmt2->get_result();
            $row = $r ? $r->fetch_assoc() : null;
            $stmt2->close();
            if ($row && intval($row['ID_carrera']) === $ID_carrera) {
                $stmt3 = $conn->prepare("SELECT ID_carrera FROM materia_carrera WHERE ID_materia = ? LIMIT 1");
                if ($stmt3) {
                    $stmt3->bind_param('i', $ID_materia);
                    $stmt3->execute();
                    $r3 = $stmt3->get_result();
                    $new = $r3 && $r3->num_rows > 0 ? intval($r3->fetch_assoc()['ID_carrera']) : null;
                    $stmt3->close();
                    if ($new) {
                        $upd = $conn->prepare("UPDATE materia SET ID_carrera = ? WHERE ID_materia = ?");
                        if ($upd) { $upd->bind_param('ii', $new, $ID_materia); $upd->execute(); $upd->close(); }
                    } else {
                        $upd = $conn->prepare("UPDATE materia SET ID_carrera = NULL WHERE ID_materia = ?");
                        if ($upd) { $upd->bind_param('i', $ID_materia); $upd->execute(); $upd->close(); }
                    }
                }
            }
        }

        return ['success' => true, 'message' => 'Materia desvinculada de la carrera'];
    }

    public function quitarDocenteDeMateria(int $ID_materia, int $ID_carrera, int $ID_docente): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM materia_docente WHERE ID_materia = ? AND ID_carrera = ? AND ID_docente = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Error DB: ' . $conn->error];
        $stmt->bind_param('iii', $ID_materia, $ID_carrera, $ID_docente);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al quitar docente de materia: ' . $err];
        }
        $stmt->close();
        $resCols = $conn->query("SHOW COLUMNS FROM `materia` LIKE 'ID_docente'");
        if ($resCols && $resCols->num_rows > 0) {
            $upd = $conn->prepare("UPDATE materia SET ID_docente = NULL WHERE ID_materia = ? AND ID_docente = ?");
            if ($upd) {
                $upd->bind_param('ii', $ID_materia, $ID_docente);
                $upd->execute();
                $upd->close();
            }
        }
        return ['success' => true, 'message' => 'Docente desvinculado de la materia'];
    }

    public function listarMateriasPorCarrera(int $ID_carrera): array
    {
        $this->ensureSchema();
        $conn = \Database::getConnection();
        $nameCol = self::detectNameColumn('materia') ?? 'nombre';
        $sql = "SELECT DISTINCT m.ID_materia, m.ID_carrera, m.`" . $conn->real_escape_string($nameCol) . "` AS nombre
            FROM materia m
            LEFT JOIN materia_carrera mc ON m.ID_materia = mc.ID_materia
            WHERE m.ID_carrera = ? OR mc.ID_carrera = ?
            ORDER BY m.`" . $conn->real_escape_string($nameCol) . "`";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $ID_carrera, $ID_carrera);
            $stmt->execute();
            $res = $stmt->get_result();
            $out = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $out[] = $r;
            }
            $stmt->close();
            return $out;
        }
        $out = [];
        $res = $conn->query("SELECT ID_materia FROM materia");
        if ($res) while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }
}
?>