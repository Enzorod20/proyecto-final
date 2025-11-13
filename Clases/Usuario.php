<?php
namespace Clases;

abstract class Usuario
{
    protected $id;
    protected $nombre;
    protected $apellido;
    protected $email;
    protected $roleId;
    protected $roleName;

    public function __construct(array $data)
    {
        $this->id = $data['ID_usuario'] ?? null;
        $this->nombre = $data['nombre'] ?? '';
        $this->apellido = $data['apellido'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->roleId = $data['ID_rol'] ?? null;
        $this->roleName = isset($data['Rol_nombre']) ? strtolower($data['Rol_nombre']) : (isset($data['rol']) ? strtolower($data['rol']) : '');
    }

    public function getName(): string
    {
        return $this->nombre ?: $this->email;
    }

    public function getRole(): string
    {
        return $this->roleName;
    }

    abstract public function renderDashboard(): string;

    public static function createFromRow(array $row)
    {
        $rol = '';
        if (isset($row['Rol_nombre'])) {
            $rol = strtolower($row['Rol_nombre']);
        } elseif (isset($row['rol'])) {
            $rol = strtolower($row['rol']);
        }
        if ($rol === 'docente') {
            require_once __DIR__ . '/Docente.php';
            return new Docente($row);
        }
        if ($rol === 'admin') {
            require_once __DIR__ . '/Admin.php';
            return new Admin($row);
        }

        require_once __DIR__ . '/Alumno.php';
        return new Alumno($row);
    }
}
?>