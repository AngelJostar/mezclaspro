<?php
namespace App\Support;

class TrainingProgramDefaults
{
    public static function programs(): array
    {
        $projects = [
            ['id' => 'induccion', 'title' => 'Induccion y seguridad operativa', 'description' => 'Programa base para ingreso, seguridad y certificacion operativa.', 'owner' => 'Operaciones', 'modules' => 4, 'students' => 9, 'status' => 'Activo', 'tone' => 'active'],
            ['id' => 'proveedores', 'title' => 'Validacion de proveedores', 'description' => 'Capacitacion para compras, cumplimiento y aprobacion documental.', 'owner' => 'Calidad', 'modules' => 3, 'students' => 3, 'status' => 'Activo', 'tone' => 'active'],
            ['id' => 'almacenamiento', 'title' => 'Buenas practicas de almacenamiento', 'description' => 'Ruta para almacen, trazabilidad, orden y control de evidencias.', 'owner' => 'Almacen', 'modules' => 5, 'students' => 5, 'status' => 'Borrador', 'tone' => 'draft'],
        ];

        $modules = [
            ['project' => 'induccion', 'stage' => 'Modulo 1', 'title' => 'Bienvenida e induccion', 'chapters' => 2, 'duration' => '21 min', 'description' => 'Presenta el objetivo, las reglas del recorrido y los criterios para aprobar.'],
            ['project' => 'induccion', 'stage' => 'Modulo 2', 'title' => 'Seguridad operativa', 'chapters' => 3, 'duration' => '43 min', 'description' => 'Refuerza la prevencion de riesgos y el uso correcto del equipo de proteccion.'],
            ['project' => 'induccion', 'stage' => 'Modulo 3', 'title' => 'Procesos esenciales', 'chapters' => 2, 'duration' => '29 min', 'description' => 'Describe procedimientos, controles y evidencias necesarias para la operacion.'],
            ['project' => 'induccion', 'stage' => 'Modulo 4', 'title' => 'Evaluacion final y certificacion', 'chapters' => 2, 'duration' => '30 min', 'description' => 'Valida el dominio del programa antes de emitir la certificacion.'],
            ['project' => 'proveedores', 'stage' => 'Modulo 1', 'title' => 'Alta y documentacion', 'chapters' => 2, 'duration' => '25 min', 'description' => 'Integra los requisitos y documentos necesarios para registrar proveedores.'],
            ['project' => 'proveedores', 'stage' => 'Modulo 2', 'title' => 'Revision de cumplimiento', 'chapters' => 3, 'duration' => '35 min', 'description' => 'Revisa criterios regulatorios, fiscales y de calidad documental.'],
            ['project' => 'proveedores', 'stage' => 'Modulo 3', 'title' => 'Validacion final', 'chapters' => 2, 'duration' => '20 min', 'description' => 'Concluye la evaluacion y autorizacion del proveedor.'],
            ['project' => 'almacenamiento', 'stage' => 'Modulo 1', 'title' => 'Recepcion de materiales', 'chapters' => 2, 'duration' => '24 min', 'description' => 'Establece controles para recibir, revisar y registrar materiales.'],
            ['project' => 'almacenamiento', 'stage' => 'Modulo 2', 'title' => 'Orden y clasificacion', 'chapters' => 2, 'duration' => '22 min', 'description' => 'Organiza productos conforme a sus condiciones y nivel de riesgo.'],
            ['project' => 'almacenamiento', 'stage' => 'Modulo 3', 'title' => 'Control de temperatura', 'chapters' => 3, 'duration' => '38 min', 'description' => 'Da seguimiento a temperatura, alertas y acciones correctivas.'],
            ['project' => 'almacenamiento', 'stage' => 'Modulo 4', 'title' => 'Inventario y trazabilidad', 'chapters' => 3, 'duration' => '40 min', 'description' => 'Controla existencias, lotes, caducidades y movimientos.'],
            ['project' => 'almacenamiento', 'stage' => 'Modulo 5', 'title' => 'Auditoria de almacen', 'chapters' => 2, 'duration' => '28 min', 'description' => 'Comprueba el cumplimiento del proceso y documenta hallazgos.'],
        ];

        foreach ($projects as &$project) {
            $project['module_definitions'] = array_values(array_filter($modules, fn ($module) => $module['project'] === $project['id']));
            foreach ($project['module_definitions'] as $index => &$module) $module['id'] = 'module-'.($index + 1);
            unset($module);
        }
        unset($project);
        return $projects;
    }
}