<?php
namespace Clases;
require_once __DIR__ . '/Usuario.php';

class Alumno extends Usuario
{
    public function renderDashboard(): string
    {
        $name = htmlspecialchars($this->getName());
        return "
        <section>
            <h1>Panel alumno</h1>
            <p>Bienvenido, {$name} (Alumno)</p>
        </section>
        HTML";
    }
}
?>