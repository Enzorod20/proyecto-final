<?php
class Database
{
    public static function getConnection()
    {
        $candidates = [
            __DIR__ . '/../conexion.php',
            __DIR__ . '/../Public/conexion.php',
            __DIR__ . '/../../Public/conexion.php'
        ];
        $connPath = null;
        foreach ($candidates as $p) {
            if (file_exists($p)) {
                $connPath = $p;
                break;
            }
        }
        if (!$connPath) {
            throw new Exception('conexion.php no encontrada. Buscados: ' . implode(', ', $candidates));
        }
        include $connPath;
        if (!isset($conn) || !$conn) {
            $extra = '';
            if (function_exists('mysqli_connect_error')) {
                $err = mysqli_connect_error();
                if ($err) {
                    $extra = ' mysqli_connect_error: ' . $err;
                }
            }
            throw new Exception('No se pudo obtener la conexión desde conexion.php.' . $extra);
        }
        return $conn;
    }
}

?>