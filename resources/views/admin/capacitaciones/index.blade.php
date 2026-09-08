<x-admin-layout>
    @php
        $isPersonnelPage = request()->routeIs('admin.capacitaciones.personal');
        $isAlumnosPage = request()->routeIs('admin.capacitaciones.alumnos');
        $isStudentsPage = $isAlumnosPage || $isPersonnelPage;
        $trainingSectionTitle = $isPersonnelPage ? 'Personal' : ($isStudentsPage ? 'Alumnos' : 'Programas');
        $availableLaboratories = $laboratories ?? collect();
        $selectedLaboratory = $selectedLaboratory ?? null;
        $selectedLaboratoryId = (int) ($selectedLaboratory?->id ?? 0);

        $personnel = [
            [
                'initials' => 'AT',
                'name' => 'Ana Torres',
                'completedPrograms' => [],
                'currentPrograms' => [
                    ['id' => 'induccion', 'name' => 'Induccion y seguridad operativa', 'progress' => 75, 'startDate' => '01/08/2026'],
                ],
                'positions' => [
                    ['name' => 'Coordinadora administrativa', 'current' => true],
                    ['name' => 'Auxiliar administrativa', 'current' => false],
                ],
                'hireDate' => '15/01/2021',
                'examScores' => [
                    ['exam' => 'Bienvenida e induccion', 'score' => 92],
                    ['exam' => 'Seguridad operativa', 'score' => 88],
                ],
                'department' => 'Administracion',
                'employmentStatus' => 'hired',
                'activity' => 'Hoy, 09:40',
                'avatar' => 'rose',
            ],
            [
                'initials' => 'CH',
                'name' => 'Carlos Hernandez',
                'completedPrograms' => [],
                'currentPrograms' => [
                    ['id' => 'induccion', 'name' => 'Induccion y seguridad operativa', 'progress' => 40, 'startDate' => '12/08/2026'],
                ],
                'positions' => [
                    ['name' => 'Almacenista', 'current' => true],
                    ['name' => 'Auxiliar de almacen', 'current' => false],
                ],
                'hireDate' => '03/06/2022',
                'examScores' => [
                    ['exam' => 'Bienvenida e induccion', 'score' => 84],
                    ['exam' => 'Seguridad operativa', 'score' => 80],
                ],
                'department' => 'Almacen',
                'employmentStatus' => 'hired',
                'activity' => 'Ayer, 16:20',
                'avatar' => 'violet',
            ],
            [
                'initials' => 'DC',
                'name' => 'Daniela Cruz',
                'completedPrograms' => ['Buenas practicas de almacenamiento'],
                'currentPrograms' => [],
                'positions' => [
                    ['name' => 'Supervisora de operaciones', 'current' => true],
                    ['name' => 'Almacenista', 'current' => false],
                ],
                'hireDate' => '20/09/2020',
                'examScores' => [
                    ['exam' => 'Recepcion de materiales', 'score' => 96],
                    ['exam' => 'Inventario y trazabilidad', 'score' => 94],
                ],
                'department' => 'Operaciones',
                'employmentStatus' => 'hired',
                'activity' => '16 ago 2026',
                'avatar' => 'mint',
            ],
            [
                'initials' => 'FO',
                'name' => 'Fernanda Ortiz',
                'completedPrograms' => [],
                'currentPrograms' => [
                    ['id' => 'citotoxicos', 'name' => 'Manejo seguro de citotoxicos', 'progress' => 20, 'startDate' => '14/08/2026'],
                ],
                'positions' => [
                    ['name' => 'Analista administrativa', 'current' => true],
                ],
                'hireDate' => '08/02/2024',
                'examScores' => [],
                'department' => 'Administracion',
                'employmentStatus' => 'hired',
                'activity' => '14 ago 2026',
                'avatar' => 'cyan',
            ],
            [
                'initials' => 'HR',
                'name' => 'Hector Ruiz',
                'completedPrograms' => [],
                'currentPrograms' => [
                    ['id' => 'proveedores', 'name' => 'Validacion de proveedores', 'progress' => 90, 'startDate' => '05/08/2026'],
                ],
                'positions' => [
                    ['name' => 'Coordinador de calidad', 'current' => true],
                    ['name' => 'Analista de calidad', 'current' => false],
                ],
                'hireDate' => '11/11/2019',
                'examScores' => [
                    ['exam' => 'Revision de cumplimiento', 'score' => 94],
                    ['exam' => 'Validacion final', 'score' => 90],
                ],
                'department' => 'Calidad',
                'employmentStatus' => 'hired',
                'activity' => 'Hoy, 08:15',
                'avatar' => 'sky',
            ],
        ];

        $learnerProfiles = collect($personnel)
            ->filter(static fn (array $personRecord): bool => ! empty($personRecord['currentPrograms']))
            ->map(function (array $personRecord, int $index): array {
                $currentProgram = $personRecord['currentPrograms'][0];

                return [
                    'id' => 'preview-' . ($index + 1) . '-' . \Illuminate\Support\Str::slug($personRecord['name']),
                    'name' => $personRecord['name'],
                    'programId' => $currentProgram['id'],
                    'programName' => $currentProgram['name'],
                    'progress' => (int) $currentProgram['progress'],
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
        $defaultLearnerProfile = $learnerProfiles[0] ?? [
            'id' => 'default',
            'name' => 'Alumno',
            'programId' => 'induccion',
            'programName' => 'Induccion y seguridad operativa',
            'progress' => 30,
        ];

        $personnel = $isPersonnelPage ? ($persistedPersonnel ?? []) : $personnel;
        $studentProgressView = request()->query('training_view', 'in_progress');
        $studentProgressView = in_array($studentProgressView, ['in_progress', 'completed'], true)
            ? $studentProgressView
            : 'in_progress';

        if ($isAlumnosPage) {
            $personnel = array_values(array_filter(
                $personnel,
                static fn (array $personRecord): bool => $studentProgressView === 'completed'
                    ? ! empty($personRecord['completedPrograms'])
                    : ! empty($personRecord['currentPrograms'])
            ));
        }

        foreach ($personnel as &$personRecord) {
            if (array_key_exists('sortDate', $personRecord)) {
                continue;
            }

            try {
                $personRecord['sortDate'] = \Carbon\Carbon::createFromFormat('d/m/Y', $personRecord['hireDate'])
                    ->startOfDay()
                    ->getTimestamp();
            } catch (\Throwable $exception) {
                $personRecord['sortDate'] = 0;
            }
        }
        unset($personRecord);

        usort($personnel, static function (array $left, array $right): int {
            $dateComparison = ($right['sortDate'] ?? 0) <=> ($left['sortDate'] ?? 0);

            return $dateComparison !== 0
                ? $dateComparison
                : (($right['sortId'] ?? 0) <=> ($left['sortId'] ?? 0));
        });

        $personnelPageSize = 200;
        $personnelTotal = count($personnel);
        $personnelPageCount = max(1, (int) ceil($personnelTotal / $personnelPageSize));
        $personnelCurrentPage = min(max(request()->integer('page', 1), 1), $personnelPageCount);
        $personnelOffset = ($personnelCurrentPage - 1) * $personnelPageSize;
        $visiblePersonnel = array_slice($personnel, $personnelOffset, $personnelPageSize);
        $personnelRangeStart = $personnelTotal === 0 ? 0 : $personnelOffset + 1;
        $personnelRangeEnd = min($personnelOffset + count($visiblePersonnel), $personnelTotal);
        $assignmentPersonnel = $assignmentPersonnel ?? array_map(
            static fn (array $personRecord, int $index): array => [
                'id' => (string) ((($personRecord['user'] ?? null)?->id) ?? ('preview-' . $index)),
                'name' => $personRecord['name'],
            ],
            $visiblePersonnel,
            array_keys($visiblePersonnel)
        );

        $programTabs = [
            ['id' => 'modules', 'label' => 'Modulos', 'icon' => 'fa-solid fa-book-open', 'active' => true],
            ['id' => 'exams', 'label' => 'Examenes', 'icon' => 'fa-regular fa-clipboard'],
            ['id' => 'tasks', 'label' => 'Tareas', 'icon' => 'fa-solid fa-list-check'],
        ];

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

        $chapters = [
            ['title' => 'Objetivo del programa', 'duration' => '12 min', 'objective' => 'Conocer la ruta, los requisitos y el minimo de aprobacion.', 'video' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE'],
            ['title' => 'Como avanzar entre modulos', 'duration' => '9 min', 'objective' => 'Entender los bloqueos, examenes y certificados.', 'video' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE'],
        ];

        $exams = [
            [
                'id' => 'induccion-bienvenida',
                'projectId' => 'induccion',
                'project' => 'Induccion y seguridad operativa',
                'module' => 'Bienvenida e induccion',
                'questions' => 3,
                'minimum' => 80,
                'status' => 'Publicado',
                'tone' => 'active',
                'items' => [
                    [
                        'text' => 'Cual es el proposito principal del programa de induccion?',
                        'options' => [
                            'Conocer la operacion, sus reglas y los criterios de aprobacion.',
                            'Sustituir la capacitacion practica del puesto.',
                            'Registrar vacaciones y permisos del personal.',
                        ],
                        'correct' => 0,
                    ],
                    [
                        'text' => 'Que debe hacerse antes de iniciar el recorrido de capacitacion?',
                        'options' => [
                            'Presentar directamente el examen final.',
                            'Revisar el objetivo y las reglas del programa.',
                            'Solicitar el certificado de terminacion.',
                        ],
                        'correct' => 1,
                    ],
                    [
                        'text' => 'Que condicion permite avanzar al siguiente modulo?',
                        'options' => [
                            'Haber abierto todos los capitulos.',
                            'Esperar a que termine el periodo de inscripcion.',
                            'Completar las actividades y alcanzar el minimo de aprobacion.',
                        ],
                        'correct' => 2,
                    ],
                ],
            ],
            [
                'id' => 'induccion-seguridad',
                'projectId' => 'induccion',
                'project' => 'Induccion y seguridad operativa',
                'module' => 'Seguridad operativa',
                'questions' => 4,
                'minimum' => 80,
                'status' => 'Publicado',
                'tone' => 'active',
                'items' => [
                    [
                        'text' => 'Que debe verificarse antes de entrar a un area controlada?',
                        'options' => [
                            'El equipo de proteccion y las condiciones de acceso.',
                            'La disponibilidad de lugares de estacionamiento.',
                            'El calendario de reuniones administrativas.',
                        ],
                        'correct' => 0,
                    ],
                    [
                        'text' => 'Que accion corresponde ante una condicion insegura?',
                        'options' => [
                            'Continuar y reportarla al terminar el turno.',
                            'Detener la actividad y reportarla de inmediato.',
                            'Resolverla sin avisar al responsable del area.',
                        ],
                        'correct' => 1,
                    ],
                    [
                        'text' => 'Cual es la funcion principal del equipo de proteccion personal?',
                        'options' => [
                            'Identificar el puesto del colaborador.',
                            'Sustituir los controles del procedimiento.',
                            'Reducir la exposicion a riesgos identificados.',
                        ],
                        'correct' => 2,
                    ],
                    [
                        'text' => 'Cuando debe documentarse un incidente operativo?',
                        'options' => [
                            'Tan pronto como sea seguro hacerlo.',
                            'Solo cuando exista una lesion.',
                            'Al cierre anual del programa.',
                        ],
                        'correct' => 0,
                    ],
                ],
            ],
            [
                'id' => 'proveedores-cumplimiento',
                'projectId' => 'proveedores',
                'project' => 'Validacion de proveedores',
                'module' => 'Revision de cumplimiento',
                'questions' => 3,
                'minimum' => 75,
                'status' => 'Borrador',
                'tone' => 'draft',
                'items' => [
                    [
                        'text' => 'Que documento debe revisarse para validar la identidad fiscal del proveedor?',
                        'options' => [
                            'Constancia de situacion fiscal vigente.',
                            'Lista interna de asistencia.',
                            'Programa anual de capacitacion.',
                        ],
                        'correct' => 0,
                    ],
                    [
                        'text' => 'Que sucede cuando un requisito documental esta vencido?',
                        'options' => [
                            'Se aprueba de manera automatica.',
                            'Se solicita su actualizacion antes de concluir la validacion.',
                            'Se elimina al proveedor sin revision.',
                        ],
                        'correct' => 1,
                    ],
                    [
                        'text' => 'Quien debe confirmar el resultado de la revision de cumplimiento?',
                        'options' => [
                            'Cualquier colaborador disponible.',
                            'Unicamente el proveedor.',
                            'El responsable autorizado del proceso.',
                        ],
                        'correct' => 2,
                    ],
                ],
            ],
        ];

        $programTasks = [
            ['projectId' => 'induccion', 'title' => 'Checklist de seguridad del area', 'project' => 'Induccion y seguridad operativa', 'stage' => 'Modulo 2', 'due' => '22 ago 2026', 'status' => 'Activa', 'tone' => 'active'],
            ['projectId' => 'induccion', 'title' => 'Subir evidencia de equipo de proteccion', 'project' => 'Induccion y seguridad operativa', 'stage' => 'Modulo 2', 'due' => '25 ago 2026', 'status' => 'Activa', 'tone' => 'active'],
            ['projectId' => 'almacenamiento', 'title' => 'Confirmar lectura del procedimiento', 'project' => 'Buenas practicas de almacenamiento', 'stage' => 'Modulo 1', 'due' => 'Sin fecha', 'status' => 'Borrador', 'tone' => 'draft'],
        ];

        $learnerTabs = [
            ['id' => 'home', 'label' => 'Inicio', 'icon' => 'fa-solid fa-house', 'active' => true],
            ['id' => 'training', 'label' => 'Mi capacitacion', 'icon' => 'fa-solid fa-play'],
            ['id' => 'tasks', 'label' => 'Tareas', 'icon' => 'fa-solid fa-list-check'],
            ['id' => 'results', 'label' => 'Resultados', 'icon' => 'fa-solid fa-chart-line'],
            ['id' => 'certificates', 'label' => 'Certificados', 'icon' => 'fa-solid fa-award'],
        ];

        $learnerTasks = [
            ['id' => 1, 'title' => 'Lista de verificacion de induccion', 'module' => 'Modulo 1', 'moduleName' => 'Bienvenida e induccion', 'status' => 'approved'],
            ['id' => 2, 'title' => 'Identificacion de riesgos en area esteril', 'module' => 'Modulo 2', 'moduleName' => 'Seguridad operativa', 'status' => 'locked', 'due' => '06 sep.'],
            ['id' => 3, 'title' => 'Registro de proceso critico', 'module' => 'Modulo 3', 'moduleName' => 'Procesos esenciales', 'status' => 'locked'],
            ['id' => 4, 'title' => 'Actividad integradora final', 'module' => 'Modulo 4', 'moduleName' => 'Cierre y certificado', 'status' => 'locked'],
        ];

        $learnerCurriculum = [
            [
                'number' => 1,
                'title' => 'Bienvenida e induccion',
                'taskId' => 1,
                'examId' => 'induccion-bienvenida',
                'minimum' => 80,
                'chapters' => [
                    [
                        'title' => 'Introduccion al programa',
                        'videos' => [
                            ['id' => 'm1-c1-v1', 'title' => 'Bienvenida a PRODIFEM', 'duration' => '6 min', 'description' => 'Conoce el objetivo general y la estructura de la capacitacion.'],
                            ['id' => 'm1-c1-v2', 'title' => 'Objetivos y alcance del programa', 'duration' => '7 min', 'description' => 'Identifica los resultados esperados y las responsabilidades del alumno.'],
                        ],
                    ],
                    [
                        'title' => 'Ruta de aprendizaje',
                        'videos' => [
                            ['id' => 'm1-c2-v1', 'title' => 'Reglas de avance y evaluacion', 'duration' => '8 min', 'description' => 'Revisa los criterios para concluir cada etapa y desbloquear la siguiente.'],
                        ],
                    ],
                ],
            ],
            [
                'number' => 2,
                'title' => 'Seguridad operativa',
                'taskId' => 2,
                'examId' => 'induccion-seguridad',
                'minimum' => 80,
                'chapters' => [
                    [
                        'title' => 'Prevencion y proteccion',
                        'videos' => [
                            ['id' => 'm2-c1-v1', 'title' => 'Equipo de proteccion personal', 'duration' => '9 min', 'description' => 'Verifica el uso y las condiciones correctas del equipo de proteccion.'],
                            ['id' => 'm2-c1-v2', 'title' => 'Buenas practicas en area esteril', 'duration' => '12 min', 'description' => 'Reconoce los puntos criticos antes de ingresar y trabajar en el area.'],
                        ],
                    ],
                    [
                        'title' => 'Respuesta operativa',
                        'videos' => [
                            ['id' => 'm2-c2-v1', 'title' => 'Identificacion y reporte de incidentes', 'duration' => '11 min', 'description' => 'Aplica el procedimiento de respuesta ante una condicion insegura.'],
                        ],
                    ],
                ],
            ],
            [
                'number' => 3,
                'title' => 'Procesos esenciales',
                'taskId' => 3,
                'examId' => 'induccion-procesos',
                'minimum' => 80,
                'chapters' => [
                    [
                        'title' => 'Proceso y controles',
                        'videos' => [
                            ['id' => 'm3-c1-v1', 'title' => 'Secuencia critica de preparacion', 'duration' => '10 min', 'description' => 'Identifica el orden obligatorio de las actividades del proceso.'],
                            ['id' => 'm3-c1-v2', 'title' => 'Registro de controles', 'duration' => '9 min', 'description' => 'Documenta controles, responsables y resultados de cada etapa.'],
                        ],
                    ],
                    [
                        'title' => 'Evidencias del proceso',
                        'videos' => [
                            ['id' => 'm3-c2-v1', 'title' => 'Integracion de evidencias', 'duration' => '10 min', 'description' => 'Reune la evidencia necesaria para demostrar el cumplimiento.'],
                        ],
                    ],
                ],
            ],
            [
                'number' => 4,
                'title' => 'Cierre y certificacion',
                'taskId' => 4,
                'examId' => 'induccion-certificacion',
                'minimum' => 80,
                'chapters' => [
                    [
                        'title' => 'Cierre del programa',
                        'videos' => [
                            ['id' => 'm4-c1-v1', 'title' => 'Revision final del recorrido', 'duration' => '9 min', 'description' => 'Confirma que todas las actividades y evidencias estan completas.'],
                            ['id' => 'm4-c1-v2', 'title' => 'Integracion del expediente', 'duration' => '8 min', 'description' => 'Prepara el expediente que respalda la terminacion del programa.'],
                        ],
                    ],
                    [
                        'title' => 'Certificacion',
                        'videos' => [
                            ['id' => 'm4-c2-v1', 'title' => 'Evaluacion y emision del certificado', 'duration' => '9 min', 'description' => 'Conoce el proceso final de evaluacion y liberacion del certificado.'],
                        ],
                    ],
                ],
            ],
        ];

        $learnerModules = [
            ['number' => 1, 'title' => 'Bienvenida e induccion', 'status' => 'approved', 'chapters' => 2, 'tasks' => 1, 'score' => 88],
            ['number' => 2, 'title' => 'Seguridad operativa', 'status' => 'current', 'progress' => 34],
            ['number' => 3, 'title' => 'Procesos esenciales', 'status' => 'locked', 'requirement' => 'Debes aprobar el modulo 2 para desbloquear este contenido.'],
            ['number' => 4, 'title' => 'Cierre y certificacion', 'status' => 'locked', 'requirement' => 'Debes aprobar el modulo 3 para desbloquear este contenido.'],
        ];
    @endphp

    <div class="training-screen" data-training-screen>
        <header class="training-page-header">
            <div class="training-page-heading">
                <h1>Personal y Capacitaciones / {{ $trainingSectionTitle }}</h1>

                @unless ($isPersonnelPage)
                    <p>{{ $isStudentsPage ? 'Gestion y seguimiento de la capacitacion del personal' : 'Diseno y administracion de programas de capacitacion' }}</p>
                @endunless
            </div>

            <div class="training-role-switch" aria-label="Cambiar vista de capacitaciones">
                <button type="button" data-role-button="learner">Usuario</button>
                <button type="button" data-role-button="admin" class="is-active">Administrador</button>
            </div>
        </header>

        <section data-role-panel="admin">
            @unless ($isStudentsPage)
                <div class="training-program-carousel" data-program-carousel>
                    <button type="button" class="training-program-carousel-arrow" data-program-carousel-previous
                        title="Programas anteriores" aria-label="Mostrar programas anteriores">
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>

                    <div class="training-program-carousel-viewport" data-program-carousel-viewport
                        role="region" aria-label="Programas de capacitacion" tabindex="0">
                        <div class="training-program-carousel-track">
                            <button type="button" class="training-program-new-tile" data-new-program>
                                <span class="training-program-new-icon"><i class="fa-solid fa-plus"></i></span>
                                <span>
                                    <strong>Nuevo programa</strong>
                                    <small>Crear programa</small>
                                </span>
                            </button>

                            @foreach ($projects as $index => $project)
                                <article class="training-program-carousel-card {{ $index === 0 ? 'is-active' : '' }}"
                                    data-program-card data-program-id="{{ $project['id'] }}">
                                    <button type="button" class="training-program-carousel-select"
                                        data-program-carousel-item data-program-id="{{ $project['id'] }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                        <span class="training-program-carousel-card-header">
                                            <span class="training-program-carousel-icon"><i class="fa-regular fa-folder-open"></i></span>
                                            <span class="training-program-status is-{{ $project['tone'] }}" data-program-status>{{ $project['status'] }}</span>
                                        </span>
                                        <strong data-program-title>{{ $project['title'] }}</strong>
                                        <span class="training-program-carousel-description" data-program-description>{{ $project['description'] }}</span>
                                        <span class="training-program-carousel-meta">
                                            <span><small>Responsable</small><b data-program-owner>{{ $project['owner'] }}</b></span>
                                            <span><small>Modulos</small><b>{{ $project['modules'] }}</b></span>
                                        </span>
                                    </button>
                                    <button type="button" class="training-program-carousel-edit" data-edit-program
                                        title="Editar programa" aria-label="Editar {{ $project['title'] }}">
                                        <span class="training-program-edit-glyph" aria-hidden="true">&#9998;</span>
                                    </button>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" class="training-program-carousel-arrow" data-program-carousel-next
                        title="Programas siguientes" aria-label="Mostrar programas siguientes">
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </div>

                <select data-program-selector hidden aria-hidden="true" tabindex="-1">
                    @foreach ($projects as $project)
                        <option value="{{ $project['id'] }}">{{ $project['title'] }}</option>
                    @endforeach
                </select>

                <nav class="training-tabs is-programs" aria-label="Secciones de programas">
                    @foreach ($programTabs as $tab)
                        <button type="button" data-program-tab="{{ $tab['id'] }}"
                            class="{{ ! empty($tab['active']) ? 'is-active' : '' }}">
                            <i class="{{ $tab['icon'] }}"></i>
                            <span>{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </nav>

                <dialog class="training-program-modal" data-program-modal aria-labelledby="training-program-modal-title">
                    <form data-program-form>
                        <header>
                            <div>
                                <span>Programa de capacitacion</span>
                                <h3 id="training-program-modal-title" data-program-modal-title>Editar programa</h3>
                            </div>
                            <button type="button" class="training-modal-close" data-close-program-modal
                                title="Cerrar" aria-label="Cerrar ventana de edicion">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </header>

                        <div class="training-program-modal-body">
                            <input type="hidden" name="program_id">

                            <label class="is-wide">
                                <span>Nombre del programa</span>
                                <input name="title" required maxlength="150">
                            </label>

                            <label class="is-wide">
                                <span>Descripcion</span>
                                <textarea name="description" rows="4" maxlength="1000"></textarea>
                            </label>

                            <label>
                                <span>Responsable</span>
                                <input name="owner" required maxlength="100">
                            </label>

                            <label>
                                <span>Estatus</span>
                                <select name="status" required>
                                    <option value="Activo">Activo</option>
                                    <option value="Borrador">Borrador</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </label>
                        </div>

                        <footer>
                            <button type="button" class="training-secondary-button" data-close-program-modal>Cancelar</button>
                            <button type="submit" class="training-primary-button">
                                <i class="fa-solid fa-check"></i>
                                <span data-program-submit-label>Guardar cambios</span>
                            </button>
                        </footer>
                    </form>
                </dialog>

                <div data-program-panel="modules">
                    <div class="training-section-heading">
                        <div>
                            <h2>Modulos y capitulos</h2>
                            <p data-selected-program-name>Induccion y seguridad operativa</p>
                        </div>

                        <button type="button" class="training-secondary-button">
                            <i class="fa-solid fa-plus"></i>
                            <span>Agregar modulo</span>
                        </button>
                    </div>

                    <div class="training-module-workspace">
                        <aside class="training-module-list" aria-label="Modulos del programa">
                            <div class="training-module-list-heading">
                                <strong>Modulos del programa</strong>
                                <span data-module-count>{{ count(array_filter($modules, fn ($module) => $module['project'] === $projects[0]['id'])) }} modulos</span>
                            </div>
                            @foreach ($modules as $index => $module)
                                <button type="button" class="training-module-item {{ $index === 0 ? 'is-active' : '' }}"
                                    data-module-item
                                    data-program-id="{{ $module['project'] }}"
                                    data-module-label="{{ $module['stage'] }}"
                                    data-module-title="{{ $module['title'] }}"
                                    data-module-description="{{ $module['description'] }}"
                                    @if ($module['project'] !== $projects[0]['id']) hidden @endif>
                                    <span class="training-module-symbol"><i class="fa-solid fa-book-open"></i></span>
                                    <span>
                                        <strong>{{ $module['stage'] }} - {{ $module['title'] }}</strong>
                                        <small>{{ $module['chapters'] }} capitulos &middot; {{ $module['duration'] }}</small>
                                    </span>
                                    <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </aside>

                        <section class="training-module-editor">
                            <div class="training-module-editor-heading">
                                <div>
                                    <span data-module-editor-label>Modulo 1</span>
                                    <h3 data-module-editor-title>Bienvenida e induccion</h3>
                                </div>
                                <button type="button" class="training-secondary-button">Editar modulo</button>
                            </div>

                            <div class="training-module-fields">
                                <label>
                                    <span>Etiqueta</span>
                                    <input value="Modulo 1" data-module-label-input>
                                </label>
                                <label>
                                    <span>Nombre del modulo</span>
                                    <input value="Bienvenida e induccion" data-module-title-input>
                                </label>
                                <label class="is-wide">
                                    <span>Descripcion</span>
                                    <input value="Presenta el objetivo, las reglas del recorrido y los criterios para aprobar." data-module-description-input>
                                </label>
                            </div>

                            <div class="training-chapter-heading">
                                <h4>Capitulos</h4>
                                <button type="button" class="training-secondary-button">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Agregar capitulo</span>
                                </button>
                            </div>

                            <div class="training-chapter-list">
                                @foreach ($chapters as $index => $chapter)
                                    <article class="training-chapter-row" data-training-chapter>
                                        <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                        <div class="training-chapter-body">
                                            <div class="training-chapter-row-heading">
                                                <strong>Capitulo {{ $index + 1 }}</strong>
                                                <button type="button" class="training-icon-button" title="Quitar capitulo" aria-label="Quitar capitulo {{ $index + 1 }}">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                            <div class="training-chapter-fields">
                                                <label><span>Titulo</span><input value="{{ $chapter['title'] }}"></label>
                                                <label>
                                                    <span>Duracion</span>
                                                    <input value="{{ $chapter['duration'] }}" readonly data-chapter-duration
                                                        data-initial-duration="{{ $chapter['duration'] }}"
                                                        title="La duracion se calcula al seleccionar el video adjunto">
                                                </label>
                                                <label><span>Objetivo</span><input value="{{ $chapter['objective'] }}"></label>
                                                <label><span>Video de capacitacion</span><input type="url" value="{{ $chapter['video'] }}"></label>
                                                <label class="training-upload-control">
                                                    <span>Archivo adjunto</span>
                                                    <input type="file" accept="video/mp4,video/quicktime,video/webm" data-chapter-video>
                                                    <strong><i class="fa-solid fa-upload"></i> Subir video</strong>
                                                    <small data-chapter-upload-status aria-live="polite">MP4, MOV o WebM</small>
                                                </label>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>

                <div data-program-panel="exams" hidden>
                    <div data-exam-index-view>
                        <div class="training-section-heading">
                            <div>
                                <h2>Examenes</h2>
                                <p>Evaluaciones y criterios de aprobacion por modulo.</p>
                            </div>
                            <button type="button" class="training-primary-button" data-new-exam>
                                <i class="fa-solid fa-plus"></i>
                                <span>Nuevo examen</span>
                            </button>
                        </div>

                        <div class="training-exam-save-feedback" data-exam-save-feedback role="status" aria-live="polite" hidden></div>

                        <div class="training-table training-program-table" role="region" aria-label="Examenes de programas" tabindex="0">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Examen</th>
                                        <th>Modulo</th>
                                        <th>Preguntas</th>
                                        <th>Minimo</th>
                                        <th>Estatus</th>
                                        <th>Editar</th>
                                        <th>Ver</th>
                                    </tr>
                                </thead>
                                <tbody data-exam-table-body>
                                    @foreach ($exams as $exam)
                                        <tr data-program-row="{{ $exam['projectId'] }}" data-exam-id="{{ $exam['id'] }}">
                                            <td data-exam-name>{{ $exam['name'] ?? ('Evaluacion de ' . strtolower($exam['module'])) }}</td>
                                            <td data-exam-module>{{ $exam['module'] }}</td>
                                            <td data-exam-question-count>{{ $exam['questions'] }}</td>
                                            <td data-exam-minimum>{{ $exam['minimum'] }}%</td>
                                            <td><span class="training-program-status is-{{ $exam['tone'] }}" data-exam-status>{{ $exam['status'] }}</span></td>
                                            <td>
                                                <button type="button" class="training-icon-button" data-edit-exam="{{ $exam['id'] }}"
                                                    title="Editar examen" aria-label="Editar {{ $exam['name'] ?? $exam['module'] }}">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="training-icon-button" data-view-exam="{{ $exam['id'] }}"
                                                    title="Ver examen" aria-label="Ver {{ $exam['name'] ?? $exam['module'] }}">
                                                    <i class="fa-regular fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-program-empty-row hidden>
                                        <td colspan="7">Este programa aun no tiene examenes registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <section class="training-exam-builder" data-exam-create-view hidden>
                        <div class="training-exam-builder-header">
                            <div>
                                <p>Capacitaciones / Programas / Examenes / Nuevo examen</p>
                                <h2>Configurar examen</h2>
                                <span>Crea la evaluacion correspondiente al modulo seleccionado.</span>
                            </div>
                            <button type="button" class="training-secondary-button" data-close-new-exam>
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>Volver a examenes</span>
                            </button>
                        </div>

                        <form data-new-exam-form>
                            <section class="training-exam-builder-section">
                                <h3>Informacion general</h3>
                                <div class="training-exam-general-grid">
                                    <label>
                                        <span>Modulo</span>
                                        <select name="module" data-new-exam-module required></select>
                                    </label>
                                    <label>
                                        <span>Nombre del examen</span>
                                        <input name="name" maxlength="180" required>
                                    </label>
                                    <label class="is-wide">
                                        <span>Instrucciones</span>
                                        <textarea name="instructions" rows="3" maxlength="1000" required></textarea>
                                    </label>
                                    <label>
                                        <span>Calificacion minima</span>
                                        <select name="minimum" required>
                                            <option value="60">60%</option>
                                            <option value="70">70%</option>
                                            <option value="75">75%</option>
                                            <option value="80" selected>80%</option>
                                            <option value="85">85%</option>
                                            <option value="90">90%</option>
                                            <option value="100">100%</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Duracion</span>
                                        <select name="duration" required>
                                            <option value="10">10 minutos</option>
                                            <option value="20" selected>20 minutos</option>
                                            <option value="30">30 minutos</option>
                                            <option value="45">45 minutos</option>
                                            <option value="60">60 minutos</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Intentos permitidos</span>
                                        <select name="attempts" required>
                                            <option value="1">1</option>
                                            <option value="2" selected>2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </label>
                                    <label class="training-exam-random-field">
                                        <span>Orden aleatorio de preguntas</span>
                                        <span class="training-switch-control">
                                            <input type="checkbox" name="randomize" checked>
                                            <span aria-hidden="true"></span>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section class="training-exam-builder-section">
                                <div class="training-exam-builder-question-heading">
                                    <div>
                                        <h3>Preguntas</h3>
                                        <span data-new-exam-summary>1 pregunta &middot; 100 puntos</span>
                                    </div>
                                    <button type="button" class="training-primary-button" data-add-new-exam-question>
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Agregar pregunta</span>
                                    </button>
                                </div>
                                <div class="training-new-exam-question-list" data-new-exam-questions></div>
                            </section>

                            <footer class="training-exam-builder-actions">
                                <button type="button" class="training-secondary-button" data-close-new-exam>Cancelar</button>
                                <button type="submit" class="training-secondary-button" name="save_mode" value="draft" formnovalidate>
                                    <i class="fa-regular fa-floppy-disk"></i>
                                    <span>Guardar borrador</span>
                                </button>
                                <button type="submit" class="training-primary-button" name="save_mode" value="published">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Guardar y publicar</span>
                                </button>
                            </footer>
                        </form>
                    </section>

                    <dialog class="training-program-modal training-exam-modal" data-exam-editor-modal
                        aria-labelledby="training-exam-editor-title">
                        <form data-exam-editor-form>
                            <header>
                                <div>
                                    <span>Examen de capacitacion</span>
                                    <h3 id="training-exam-editor-title">Editar examen</h3>
                                </div>
                                <button type="button" class="training-modal-close" data-close-exam-editor
                                    title="Cerrar" aria-label="Cerrar editor de examen">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </header>

                            <div class="training-exam-editor-body">
                                <input type="hidden" name="exam_id">

                                <div class="training-exam-meta-fields">
                                    <label>
                                        <span>Modulo</span>
                                        <input name="module" required maxlength="150">
                                    </label>
                                    <label>
                                        <span>Minimo aprobatorio</span>
                                        <span class="training-percent-field">
                                            <input type="number" name="minimum" min="0" max="100" step="1" required>
                                            <strong>%</strong>
                                        </span>
                                    </label>
                                </div>

                                <div class="training-exam-questions-heading">
                                    <div>
                                        <h4>Preguntas</h4>
                                        <span data-exam-editor-count></span>
                                    </div>
                                    <button type="button" class="training-secondary-button" data-add-exam-question>
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Agregar pregunta</span>
                                    </button>
                                </div>

                                <div class="training-exam-question-list" data-exam-question-editor></div>
                            </div>

                            <footer>
                                <button type="button" class="training-secondary-button" data-close-exam-editor>Cancelar</button>
                                <button type="submit" class="training-primary-button">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Guardar cambios</span>
                                </button>
                            </footer>
                        </form>
                    </dialog>

                    <dialog class="training-program-modal training-exam-modal" data-exam-view-modal
                        aria-labelledby="training-exam-view-title">
                        <form>
                            <header>
                                <div>
                                    <span>Vista del alumno</span>
                                    <h3 id="training-exam-view-title">Ver examen</h3>
                                </div>
                                <button type="button" class="training-modal-close" data-close-exam-view
                                    title="Cerrar" aria-label="Cerrar vista del examen">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </header>

                            <div class="training-exam-preview" data-exam-preview></div>

                            <footer>
                                <button type="button" class="training-secondary-button" data-close-exam-view>Cerrar</button>
                            </footer>
                        </form>
                    </dialog>
                </div>

                <div data-program-panel="tasks" hidden>
                    <div class="training-section-heading">
                        <div>
                            <h2>Tareas</h2>
                            <p>Actividades y evidencias asociadas a cada modulo.</p>
                        </div>
                        <button type="button" class="training-primary-button">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nueva tarea</span>
                        </button>
                    </div>

                    <div class="training-table training-program-table" role="region" aria-label="Tareas de programas" tabindex="0">
                        <table>
                            <thead><tr><th>Tarea</th><th>Programa</th><th>Modulo</th><th>Fecha limite</th><th>Estatus</th><th>Acciones</th></tr></thead>
                            <tbody>
                                @foreach ($programTasks as $task)
                                    <tr data-program-row="{{ $task['projectId'] }}">
                                        <td><strong>{{ $task['title'] }}</strong></td>
                                        <td>{{ $task['project'] }}</td>
                                        <td>{{ $task['stage'] }}</td>
                                        <td>{{ $task['due'] }}</td>
                                        <td><span class="training-program-status is-{{ $task['tone'] }}">{{ $task['status'] }}</span></td>
                                        <td><button type="button" class="training-icon-button" title="Editar tarea" aria-label="Editar {{ $task['title'] }}"><i class="fa-solid fa-pen"></i></button></td>
                                    </tr>
                                @endforeach
                                <tr data-program-empty-row hidden>
                                    <td colspan="6">Este programa aun no tiene tareas registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @else

            @if ($isAlumnosPage)
                <div class="training-student-toolbar">
                    <button type="button" class="training-primary-button training-new-assignment-button"
                        data-open-new-training @disabled(empty($assignmentPersonnel))>
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Nueva Capacitaci&oacute;n</span>
                    </button>

                    <nav class="training-student-view-switch" aria-label="Filtrar alumnos por avance de capacitacion">
                        <a href="{{ route('admin.capacitaciones.alumnos', ['training_view' => 'in_progress']) }}"
                            class="{{ $studentProgressView === 'in_progress' ? 'is-active' : '' }}"
                            @if ($studentProgressView === 'in_progress') aria-current="page" @endif>
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            <span>En curso</span>
                        </a>
                        <a href="{{ route('admin.capacitaciones.alumnos', ['training_view' => 'completed']) }}"
                            class="{{ $studentProgressView === 'completed' ? 'is-active' : '' }}"
                            @if ($studentProgressView === 'completed') aria-current="page" @endif>
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            <span>Concluidos</span>
                        </a>
                    </nav>
                </div>
            @endif

            @unless ($isPersonnelPage)
                <div class="training-section-heading">
                    <h2>Avance del personal</h2>
                </div>
            @endunless

            @if ($isPersonnelPage)
                <section class="training-laboratory-selector" aria-labelledby="personnel-laboratory-carousel-title">
                    <div class="training-laboratory-selector-heading">
                        <h2 id="personnel-laboratory-carousel-title">Selecciona una central</h2>
                        <p>Consulta todas las centrales o selecciona una central espec&iacute;fica.</p>
                    </div>

                    @if ($availableLaboratories->isNotEmpty())
                        <div class="training-laboratory-carousel-shell">
                            <button type="button" class="training-laboratory-nav" data-personnel-laboratory-previous
                                title="Central anterior" aria-label="Central anterior">
                                <span aria-hidden="true">&lsaquo;</span>
                            </button>

                            <div class="training-laboratory-carousel" data-personnel-laboratory-carousel>
                                <a href="{{ route('admin.capacitaciones.personal', request()->except(['page', 'laboratory_id'])) }}"
                                    class="training-laboratory-card {{ $selectedLaboratoryId === 0 ? 'is-active' : '' }}"
                                    @if ($selectedLaboratoryId === 0) data-selected-laboratory aria-current="page" @endif>
                                    <span class="training-laboratory-card-heading">
                                        <span class="training-laboratory-letter" aria-hidden="true">T</span>
                                        <span class="training-laboratory-card-copy">
                                            <strong>Todas</strong>
                                            <small>Todas las centrales</small>
                                            <em>Cat&aacute;logo consolidado</em>
                                        </span>
                                    </span>
                                    <span class="training-laboratory-card-meta">
                                        <span>
                                            <i aria-hidden="true"></i>
                                            Activas
                                        </span>
                                        <span>{{ $availableLaboratories->count() }} {{ $availableLaboratories->count() === 1 ? 'central' : 'centrales' }}</span>
                                    </span>
                                </a>

                                @foreach ($availableLaboratories as $laboratory)
                                    @php
                                        $isSelectedLaboratory = $selectedLaboratoryId === (int) $laboratory->id;
                                        $personnelCount = (int) ($laboratory->personnel_count ?? 0);
                                    @endphp
                                    <a href="{{ route('admin.capacitaciones.personal', array_merge(request()->except(['page']), ['laboratory_id' => $laboratory->id])) }}"
                                        class="training-laboratory-card {{ $isSelectedLaboratory ? 'is-active' : '' }}"
                                        @if ($isSelectedLaboratory) data-selected-laboratory aria-current="page" @endif>
                                        <span class="training-laboratory-card-heading">
                                            <span class="training-laboratory-letter" aria-hidden="true">C</span>
                                            <span class="training-laboratory-card-copy">
                                                <strong>{{ $laboratory->nombre }}</strong>
                                                <small>{{ $laboratory->estado ?: 'Sin estado registrado' }}</small>
                                                <em>{{ $laboratory->direccion ?: 'Sin direcci&oacute;n registrada' }}</em>
                                            </span>
                                        </span>
                                        <span class="training-laboratory-card-meta">
                                            <span>
                                                <i aria-hidden="true"></i>
                                                Activa
                                            </span>
                                            <span>{{ $personnelCount }} {{ $personnelCount === 1 ? 'persona' : 'personas' }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <button type="button" class="training-laboratory-nav" data-personnel-laboratory-next
                                title="Central siguiente" aria-label="Central siguiente">
                                <span aria-hidden="true">&rsaquo;</span>
                            </button>
                        </div>
                    @else
                        <p class="training-laboratory-empty">No hay centrales activas disponibles.</p>
                    @endif
                </section>

                <div class="training-header-actions training-personnel-actions">
                    <button type="button" class="training-primary-button" data-open-personnel-create>
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Nuevo personal</span>
                    </button>
                    <button type="button" class="training-secondary-button">Personal concluido</button>
                </div>
            @endif

            <div class="training-filters {{ $isPersonnelPage ? 'is-personnel' : '' }}">
                @unless ($isPersonnelPage)
                    <label>
                        <span>Nombre o puesto</span>
                        <div class="training-input-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" placeholder="Buscar por nombre o puesto">
                        </div>
                    </label>
                @endunless

                <label>
                    <span>Departamento</span>
                    <select>
                        <option>Todos los departamentos</option>
                        <option>Administracion</option>
                        <option>Almacen</option>
                        <option>Calidad</option>
                        <option>Operaciones</option>
                    </select>
                </label>

                <button type="button" class="training-filter-button">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filtros</span>
                </button>
            </div>

            <div class="training-table {{ $isPersonnelPage ? 'training-personnel-table' : '' }}"
                @if ($isPersonnelPage) data-sticky-x-mode="fixed" @endif role="region"
                aria-label="{{ $isPersonnelPage ? 'Informacion del personal' : ($studentProgressView === 'completed' ? 'Alumnos con programas concluidos' : 'Alumnos con capacitaciones en curso') }}" tabindex="0">
                <table>
                    <thead>
                        @if ($isPersonnelPage)
                            <tr>
                                <th rowspan="2">Personal</th>
                                <th rowspan="2">Editar</th>
                                <th rowspan="2">Puesto(s)</th>
                                <th rowspan="2">Fecha de ingreso</th>
                                <th rowspan="2">Programas concluidos</th>
                                <th rowspan="2">Programas en curso</th>
                                <th rowspan="2" class="training-score-heading">
                                    <span>Promedio de calificaci&oacute;n</span>
                                    <span>en programas de</span>
                                    <span>capacitaci&oacute;n</span>
                                </th>
                                <th rowspan="2">Departamento</th>
                                <th rowspan="2">Estatus laboral</th>
                                <th rowspan="2">Ultima actividad</th>
                                <th rowspan="2">Nueva capacitaci&oacute;n</th>
                                <th rowspan="2">ID usuario</th>
                                <th colspan="2" class="training-user-group is-software">
                                    <i class="fa-solid fa-desktop" aria-hidden="true"></i>
                                    Acceso al software
                                </th>
                                <th colspan="2" class="training-user-group is-training">
                                    <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                                    Acceso a capacitaci&oacute;n
                                </th>
                                <th rowspan="2">Central</th>
                                <th rowspan="2">Roles</th>
                                <th rowspan="2">Editar accesos</th>
                                <th rowspan="2">Bloqueo</th>
                            </tr>
                            <tr class="training-user-subheading">
                                <th class="is-software">Usuario software</th>
                                <th class="is-software">Contrase&ntilde;a software</th>
                                <th class="is-training">Usuario capacitaci&oacute;n</th>
                                <th class="is-training">Contrase&ntilde;a capacitaci&oacute;n</th>
                            </tr>
                        @else
                            <tr>
                                <th>Alumno</th>
                                <th>Programas concluidos</th>
                                <th>Programas en curso</th>
                                <th>Fecha de inicio</th>
                                <th>Departamento</th>
                                <th>Estatus</th>
                                <th>Ultima actividad</th>
                                <th>Nueva Capacitacion</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach ($visiblePersonnel as $person)
                            @php
                                $personUser = $person['user'] ?? null;
                                $credentials = $personUser && auth()->user()->can('usuarios')
                                    ? \App\Support\PersonnelCredentialDisplay::forUser($personUser)
                                    : null;
                            @endphp
                            <tr data-student-row data-student-name="{{ $person['name'] }}"
                                data-student-index="{{ $loop->index }}">
                                <td>
                                    <div class="training-person">
                                        <span class="training-avatar is-{{ $person['avatar'] }}">{{ $person['initials'] }}</span>
                                        <strong>{{ $person['name'] }}</strong>
                                    </div>
                                </td>
                                @if ($isPersonnelPage)
                                    <td class="training-user-action-cell">
                                        @if ($personUser && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('menu.capacitaciones.personal')))
                                            <button type="button" class="training-user-edit-button"
                                                data-personnel-edit-url="{{ route('admin.capacitaciones.personal.edit', $personUser) }}"
                                                aria-label="Editar datos de {{ $person['name'] }}">
                                                <span>Editar</span>
                                            </button>
                                        @else
                                            <span class="training-empty-program">-</span>
                                        @endif
                                    </td>
                                    <td class="training-position-cell">
                                        <div class="training-position-list">
                                            @forelse ($person['positions'] as $position)
                                                <span class="{{ $position['current'] ? 'is-current' : '' }}">
                                                    {{ $position['name'] }}
                                                    @if ($position['current'])
                                                        <small>Actual</small>
                                                    @endif
                                                </span>
                                            @empty
                                                <span class="training-empty-program">Sin puesto registrado</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="training-hire-date">{{ $person['hireDate'] }}</td>
                                @endif
                                <td class="training-program-cell">
                                    @forelse ($person['completedPrograms'] as $program)
                                        <span class="training-completed-program">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                            <span>{{ $program }}</span>
                                        </span>
                                    @empty
                                        <span class="training-empty-program">Ninguno</span>
                                    @endforelse
                                </td>
                                <td class="training-program-cell" data-current-programs>
                                    @forelse ($person['currentPrograms'] as $program)
                                        <div class="training-current-program" data-current-program-id="{{ $program['id'] }}">
                                            <div class="training-current-program-heading">
                                                <span>{{ $program['name'] }}</span>
                                                <strong>{{ $program['progress'] }}%</strong>
                                            </div>
                                            <span class="training-progress-track" aria-label="Progreso: {{ $program['progress'] }}%">
                                                <span style="width: {{ $program['progress'] }}%"></span>
                                            </span>
                                        </div>
                                    @empty
                                        <span class="training-empty-program" data-empty-current-program>Ninguno</span>
                                    @endforelse
                                </td>
                                @if ($isAlumnosPage)
                                    <td class="training-start-date-cell" data-training-start-dates>
                                        <div class="training-start-date-list">
                                            @forelse ($person['currentPrograms'] as $program)
                                                <span data-start-date-program-id="{{ $program['id'] }}">
                                                    {{ $program['startDate'] ?? 'Sin fecha' }}
                                                </span>
                                            @empty
                                                <span class="training-empty-program" data-empty-start-date>Sin fecha</span>
                                            @endforelse
                                        </div>
                                    </td>
                                @endif
                                @if ($isPersonnelPage)
                                    @php
                                        $scoreCount = count($person['examScores']);
                                        $averageScore = $scoreCount > 0
                                            ? (int) round(array_sum(array_column($person['examScores'], 'score')) / $scoreCount)
                                            : null;
                                    @endphp
                                    <td class="training-score-cell">
                                        @if ($averageScore !== null)
                                            <div class="training-score-summary">
                                                <strong>{{ $averageScore }}%</strong>
                                                <span>{{ $scoreCount }} {{ $scoreCount === 1 ? 'examen' : 'examenes' }}</span>
                                            </div>
                                            <div class="training-score-list">
                                                @foreach ($person['examScores'] as $examScore)
                                                    <span>
                                                        <span title="{{ $examScore['exam'] }}">{{ $examScore['exam'] }}</span>
                                                        <strong>{{ $examScore['score'] }}%</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="training-empty-program">Sin calificaciones</span>
                                        @endif
                                    </td>
                                @endif
                                <td>{{ $person['department'] }}</td>
                                <td>
                                    <div class="training-employment-status is-{{ $person['employmentStatus'] }}"
                                        data-employment-control>
                                        <span class="training-employment-dot" aria-hidden="true"></span>
                                        <select data-student-status aria-label="Estatus laboral de {{ $person['name'] }}">
                                            <option value="hired" @selected($person['employmentStatus'] === 'hired')>Contratado</option>
                                            <option value="inactive" @selected($person['employmentStatus'] === 'inactive')>Baja</option>
                                        </select>
                                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                    </div>
                                </td>
                                <td>{{ $person['activity'] }}</td>
                                <td>
                                    <button type="button" class="training-add-training-button" data-add-training
                                        aria-label="Agregar capacitacion para {{ $person['name'] }}">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        <span>Agregar</span>
                                    </button>
                                </td>
                                @if ($isPersonnelPage)
                                    <td class="training-user-id-cell">{{ $personUser?->id ?? '-' }}</td>
                                    <td class="training-user-access-cell is-software">
                                        @if ($personUser)
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$personUser" field="username" compact />
                                            @else
                                                <span>{{ $personUser->username ?: 'Sin usuario' }}</span>
                                            @endcan
                                        @else
                                            <span class="training-empty-program">Sin acceso</span>
                                        @endif
                                    </td>
                                    <td class="training-user-access-cell is-software">
                                        @if ($personUser)
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$personUser" field="password" compact
                                                    :display-value="$credentials['software']['value']"
                                                    :empty-label="$credentials['software']['empty_label']"
                                                    :empty-hint="$credentials['software']['empty_hint']" />
                                            @else
                                                <span class="training-restricted-value">Restringido</span>
                                            @endcan
                                        @else
                                            <span class="training-empty-program">Sin acceso</span>
                                        @endif
                                    </td>
                                    <td class="training-user-access-cell is-training">
                                        @if ($personUser)
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$personUser" field="training_username" compact />
                                            @else
                                                <span>{{ $personUser->training_username ?: 'Sin usuario' }}</span>
                                            @endcan
                                        @else
                                            <span class="training-empty-program">Sin acceso</span>
                                        @endif
                                    </td>
                                    <td class="training-user-access-cell is-training">
                                        @if ($personUser)
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$personUser" field="training_password" compact
                                                    :display-value="$credentials['training']['value']"
                                                    :empty-label="$credentials['training']['empty_label']"
                                                    :empty-hint="$credentials['training']['empty_hint']" />
                                            @else
                                                <span class="training-restricted-value">Restringido</span>
                                            @endcan
                                        @else
                                            <span class="training-empty-program">Sin acceso</span>
                                        @endif
                                    </td>
                                    <td>{{ $person['central'] ?? 'Sin central asignada' }}</td>
                                    <td data-role-cell="{{ $personUser?->id }}">
                                        @if ($personUser)
                                            {{ $personUser->roles
                                                ->map(fn ($role) => \App\Support\AdminMenuAccess::roleLabel($role->name))
                                                ->join(', ') ?: 'Sin rol asignado' }}
                                        @else
                                            Sin rol asignado
                                        @endif
                                    </td>
                                    <td class="training-user-action-cell">
                                        @if ($personUser && $isSuperAdmin)
                                            <button type="button" class="training-user-edit-button"
                                                data-personnel-role-access-open
                                                data-action="{{ route('admin.users.role-access.update', $personUser) }}"
                                                data-user-name="{{ $person['name'] }}"
                                                data-current-role="{{ $personUser->roles->first()?->name ?? '' }}"
                                                data-menu-permissions='@json($personUser->permissions->pluck('name')->filter(fn ($permission) => str_starts_with($permission, 'menu.'))->values())'>
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                                <span>Editar</span>
                                            </button>
                                        @else
                                            <span class="training-empty-program">-</span>
                                        @endif
                                    </td>
                                    <td class="training-user-action-cell">
                                        @if ($personUser)
                                            @can('usuarios')
                                                <x-access-status-toggle type="user" :target="$personUser"
                                                    :is-active="$personUser->is_active" compact menu />
                                            @else
                                                <span class="training-access-state {{ $personUser->is_active ? 'is-active' : 'is-blocked' }}">
                                                    {{ $personUser->is_active ? 'Activo' : 'Bloqueado' }}
                                                </span>
                                            @endcan
                                        @else
                                            <span class="training-empty-program">Sin acceso</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        @if ($visiblePersonnel === [])
                            <tr>
                                <td colspan="{{ $isPersonnelPage ? 20 : ($isAlumnosPage ? 8 : 7) }}" class="training-table-empty">
                                    @if ($isPersonnelPage)
                                        No hay personal registrado.
                                    @else
                                        {{ $studentProgressView === 'completed' ? 'No hay alumnos con programas concluidos.' : 'No hay alumnos con capacitaciones en curso.' }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

                <footer class="training-pagination" aria-label="Paginacion de personal en bloques de 200">
                <span>{{ $personnelRangeStart }}-{{ $personnelRangeEnd }} de {{ $personnelTotal }}</span>

                @if ($personnelCurrentPage > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $personnelCurrentPage - 1]) }}" aria-label="Pagina anterior">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                @else
                    <button type="button" aria-label="Pagina anterior" disabled>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                @endif

                @if ($personnelCurrentPage < $personnelPageCount)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $personnelCurrentPage + 1]) }}" aria-label="Pagina siguiente">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                @else
                    <button type="button" aria-label="Pagina siguiente" disabled>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                @endif
            </footer>

            @if ($isPersonnelPage)
                @include('admin.capacitaciones.partials.personnel-create-modal')
                @include('admin.capacitaciones.partials.personnel-edit-modal')
                @include('admin.capacitaciones.partials.personnel-user-management')
            @endif

            <dialog class="training-program-modal training-assignment-modal" data-training-assignment-modal
                aria-labelledby="training-assignment-title">
                <form data-training-assignment-form>
                    <header>
                        <div>
                            <span>Nueva capacitacion</span>
                            <h3 id="training-assignment-title">Seleccionar programa o modulos</h3>
                            <p data-assignment-student-summary>Alumno: <strong data-assignment-student></strong></p>
                        </div>
                        <button type="button" class="training-modal-close" data-close-training-assignment
                            title="Cerrar" aria-label="Cerrar seleccion de capacitacion">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="training-assignment-body">
                        <label class="training-assignment-student-picker" data-assignment-student-picker hidden>
                            <span>Alumno <b>*</b></span>
                            <span class="training-assignment-student-control">
                                <select data-assignment-student-select aria-label="Seleccionar alumno para la capacitacion">
                                    <option value="">Selecciona un alumno</option>
                                    @foreach ($assignmentPersonnel as $person)
                                        <option value="{{ $person['id'] }}" data-student-name="{{ $person['name'] }}">
                                            {{ $person['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </span>
                        </label>

                        <label class="training-assignment-start-date">
                            <span>Fecha de inicio <b>*</b></span>
                            <input type="date" required data-assignment-start-date>
                        </label>

                        <label class="training-assignment-select-all">
                            <input type="checkbox" data-assignment-select-all>
                            <span>
                                <strong>Seleccionar todo</strong>
                                <small>Incluye todos los programas y sus modulos.</small>
                            </span>
                        </label>

                        <div class="training-assignment-program-list">
                            @foreach ($projects as $project)
                                @php
                                    $projectModules = array_values(array_filter(
                                        $modules,
                                        fn ($module) => $module['project'] === $project['id']
                                    ));
                                @endphp
                                <section class="training-assignment-program" data-assignment-program
                                    data-program-id="{{ $project['id'] }}" data-program-name="{{ $project['title'] }}">
                                    <label class="training-assignment-program-choice">
                                        <input type="checkbox" data-assignment-program-checkbox>
                                        <span>
                                            <strong>{{ $project['title'] }}</strong>
                                            <small>Programa completo | {{ count($projectModules) }} modulos</small>
                                        </span>
                                    </label>

                                    <div class="training-assignment-module-list">
                                        @foreach ($projectModules as $module)
                                            <label>
                                                <input type="checkbox" data-assignment-module-checkbox
                                                    data-module-label="{{ $module['stage'] }} - {{ $module['title'] }}">
                                                <span>
                                                    <strong>{{ $module['stage'] }}</strong>
                                                    <small>{{ $module['title'] }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>

                    <footer>
                        <span class="training-assignment-count" data-assignment-count>0 modulos seleccionados</span>
                        <div class="training-assignment-actions">
                            <button type="button" class="training-secondary-button" data-close-training-assignment>Cancelar</button>
                            <button type="submit" class="training-primary-button" data-confirm-training-assignment disabled>
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                <span>Agregar capacitacion</span>
                            </button>
                        </div>
                    </footer>
                </form>
            </dialog>
            @endunless
        </section>

        <section data-role-panel="learner" hidden>
            @if ($isAlumnosPage)
                <div class="training-learner-access">
                    <label>
                        <span>Seleccionar usuario en capacitaci&oacute;n</span>
                        <span class="training-learner-select-control">
                            <select data-learner-selector aria-label="Seleccionar usuario en capacitacion">
                                @foreach ($learnerProfiles as $learnerProfile)
                                    <option value="{{ $learnerProfile['id'] }}"
                                        data-learner-name="{{ $learnerProfile['name'] }}"
                                        data-program-id="{{ $learnerProfile['programId'] }}"
                                        data-program-name="{{ $learnerProfile['programName'] }}"
                                        data-progress="{{ $learnerProfile['progress'] }}">
                                        {{ $learnerProfile['name'] }} - {{ $learnerProfile['programName'] }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </span>
                    </label>

                    <button type="button" class="training-primary-button" data-open-learner-panel
                        @disabled(empty($learnerProfiles))>
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <span>Abrir panel</span>
                    </button>
                </div>
            @endif

            <nav class="training-tabs training-learner-tabs" aria-label="Secciones de usuario" role="tablist">
                @foreach ($learnerTabs as $tab)
                    <button type="button" id="learner-tab-{{ $tab['id'] }}"
                        data-learner-tab="{{ $tab['id'] }}" role="tab"
                        aria-controls="learner-panel-{{ $tab['id'] }}"
                        aria-selected="{{ ! empty($tab['active']) ? 'true' : 'false' }}"
                        tabindex="{{ ! empty($tab['active']) ? '0' : '-1' }}"
                        class="{{ ! empty($tab['active']) ? 'is-active' : '' }}">
                        <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="training-obligation-flow" aria-label="Flujo obligatorio de capacitacion">
                <div class="training-obligation-stage is-active" data-obligation-stage="content">
                    <span><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <strong>Contenido</strong>
                    <small>Videos por capitulo</small>
                </div>
                <i class="fa-solid fa-arrow-right training-obligation-arrow" aria-hidden="true"></i>
                <div class="training-obligation-stage" data-obligation-stage="task">
                    <span><i class="fa-regular fa-clipboard" aria-hidden="true"></i></span>
                    <strong>Tarea</strong>
                    <small>Entrega obligatoria</small>
                </div>
                <i class="fa-solid fa-arrow-right training-obligation-arrow" aria-hidden="true"></i>
                <div class="training-obligation-stage" data-obligation-stage="exam">
                    <span><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
                    <strong>Examen</strong>
                    <small>Calificacion minima 80%</small>
                </div>
                <i class="fa-solid fa-arrow-right training-obligation-arrow" aria-hidden="true"></i>
                <div class="training-obligation-stage" data-obligation-stage="next">
                    <span><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    <strong>Siguiente modulo</strong>
                    <small>Se habilita al aprobar</small>
                </div>
            </div>

            <div id="learner-panel-home" class="training-learner-view" data-learner-panel="home"
                role="tabpanel" aria-labelledby="learner-tab-home">
                <div class="training-learner-hero">
                    <div>
                        <span data-next-obligation-eyebrow>Siguiente obligacion</span>
                        <h2>Buenos dias, <span data-active-learner-name>{{ $learnerProfiles[0]['name'] ?? 'Alumno' }}</span></h2>
                        <p data-next-obligation-summary>Continua con el siguiente video de Seguridad operativa.</p>
                    </div>

                    <button type="button" class="training-primary-button" data-next-obligation-action>
                        <i class="fa-solid fa-play" aria-hidden="true"></i>
                        <span data-next-obligation-label>Continuar video</span>
                    </button>
                </div>

                <div class="training-learner-metrics">
                    <article>
                        <span>Progreso general</span>
                        <strong data-overall-progress>33%</strong>
                        <div class="training-progress-track" aria-label="Progreso general">
                            <span style="width: 33%" data-overall-progress-fill></span>
                        </div>
                    </article>
                    <article>
                        <span>Modulos aprobados</span>
                        <strong data-approved-module-count>1/4</strong>
                    </article>
                    <article>
                        <span>Tareas abiertas</span>
                        <strong data-learner-open-task-count>1</strong>
                    </article>
                    <article>
                        <span>Ultima calificacion</span>
                        <strong data-latest-exam-score>88%</strong>
                    </article>
                </div>

                <div class="training-learner-grid is-outline-only">
                    <aside class="training-route-panel">
                        <div class="training-route-heading">
                            <span>Contenido del modulo</span>
                            <strong>Avance obligatorio</strong>
                        </div>
                        <div class="training-content-outline" data-content-outline></div>
                    </aside>
                </div>
            </div>

            <div id="learner-panel-training" class="training-learner-view" data-learner-panel="training"
                role="tabpanel" aria-labelledby="learner-tab-training" hidden>
                <section class="training-learner-section">
                    <header class="training-learner-section-heading">
                        <div>
                            <h2>Mi capacitaci&oacute;n</h2>
                            <p>Ruta obligatoria: completa cada m&oacute;dulo para desbloquear el siguiente.</p>
                        </div>
                    </header>

                    <div class="training-learning-path" aria-label="Avance por modulos">
                        @foreach ($learnerModules as $module)
                            <div class="training-learning-path-step is-{{ $module['status'] }}"
                                data-learning-path-step="{{ $module['number'] }}">
                                <span>{{ $module['number'] }}</span>
                                <strong>{{ $module['status'] === 'approved' ? 'Aprobado' : ($module['status'] === 'current' ? 'En curso' : 'Bloqueado') }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="training-module-route" data-learner-module-route>
                        @foreach ($learnerModules as $module)
                            <article class="training-learner-module is-{{ $module['status'] }}"
                                data-learner-module="{{ $module['number'] }}" data-module-status="{{ $module['status'] }}">
                                <div class="training-learner-module-marker">
                                    @if ($module['status'] === 'approved')
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    @elseif ($module['status'] === 'locked')
                                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    @else
                                        <span>{{ $module['number'] }}</span>
                                    @endif
                                </div>

                                <div class="training-learner-module-content">
                                    <h3>Modulo {{ $module['number'] }} &middot; {{ $module['title'] }}</h3>

                                    <div data-module-detail>
                                        @if ($module['status'] === 'approved')
                                            <div class="training-module-meta">
                                                <span><i class="fa-regular fa-book-open" aria-hidden="true"></i> {{ $module['chapters'] }} capitulos</span>
                                                <span><i class="fa-regular fa-clipboard" aria-hidden="true"></i> {{ $module['tasks'] }} tarea</span>
                                                <span><i class="fa-regular fa-chart-pie" aria-hidden="true"></i> Examen <strong>{{ $module['score'] }}%</strong></span>
                                            </div>
                                        @elseif ($module['status'] === 'current')
                                            <div class="training-module-progress">
                                                <strong>{{ $module['progress'] }}%</strong>
                                                <div class="training-progress-track" aria-label="Progreso del modulo: {{ $module['progress'] }}%">
                                                    <span style="width: {{ $module['progress'] }}%"></span>
                                                </div>
                                            </div>
                                        @else
                                            <p><strong>Bloqueado</strong> &middot; {{ $module['requirement'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="training-learner-module-actions">
                                    @if ($module['status'] === 'approved')
                                        <span class="training-state-pill is-approved"><i class="fa-solid fa-check" aria-hidden="true"></i> Aprobado</span>
                                        <button type="button" class="training-secondary-button" data-learner-go-to="home">Consultar</button>
                                    @elseif ($module['status'] === 'current')
                                        <span class="training-state-pill is-current">En curso</span>
                                        <button type="button" class="training-primary-button" data-learner-go-to="home">Continuar modulo</button>
                                    @else
                                        <span class="training-state-pill is-locked">Bloqueado</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="training-information-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <p><strong>Importante:</strong> una vez aprobado un modulo, su contenido queda disponible en modo de consulta.</p>
                    </div>
                </section>
            </div>

            <div id="learner-panel-tasks" class="training-learner-view" data-learner-panel="tasks"
                role="tabpanel" aria-labelledby="learner-tab-tasks" hidden>
                <section class="training-learner-section">
                    <header class="training-learner-section-heading">
                        <div>
                            <h2>Mis tareas</h2>
                            <p>Las tareas aparecen conforme avanzas en los modulos.</p>
                        </div>
                    </header>

                    <div class="training-active-task-summary">
                        <span><i aria-hidden="true"></i> <b data-active-task-summary>1 tarea activa</b></span>
                    </div>

                    <div class="training-task-filters" role="group" aria-label="Filtrar tareas por estado">
                        <button type="button" class="is-active" data-task-filter="all">Todas</button>
                        <button type="button" data-task-filter="pending">Pendientes</button>
                        <button type="button" data-task-filter="review">En revision</button>
                        <button type="button" data-task-filter="corrections">Correcciones</button>
                        <button type="button" data-task-filter="approved">Aprobadas</button>
                    </div>

                    <div class="training-task-table-wrap">
                        <table class="training-task-table">
                            <thead>
                                <tr>
                                    <th>Tarea</th>
                                    <th>Modulo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($learnerTasks as $task)
                                    <tr class="is-{{ $task['status'] }}" data-learner-task="{{ $task['id'] }}"
                                        data-task-status="{{ $task['status'] }}" data-task-title="{{ $task['title'] }}"
                                        data-task-module="{{ $task['module'] }} - {{ $task['moduleName'] }}">
                                        <td>
                                            <div class="training-task-name">
                                                <span>{{ $task['id'] }}</span>
                                                <strong>{{ $task['title'] }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span>{{ $task['module'] }}</span>
                                            <strong>{{ $task['moduleName'] }}</strong>
                                        </td>
                                        <td data-task-status-cell>
                                            @if ($task['status'] === 'approved')
                                                <span class="training-state-pill is-approved"><i class="fa-regular fa-circle-check" aria-hidden="true"></i> Aprobada</span>
                                            @elseif ($task['status'] === 'pending')
                                                <span class="training-state-pill is-pending"><i class="fa-regular fa-clock" aria-hidden="true"></i> Vence {{ $task['due'] }}</span>
                                            @else
                                                <span class="training-state-pill is-locked"><i class="fa-solid fa-lock" aria-hidden="true"></i> Bloqueada</span>
                                            @endif
                                        </td>
                                        <td data-task-action-cell>
                                            @if ($task['status'] === 'approved')
                                                <button type="button" class="training-secondary-button" data-view-task>Ver entrega</button>
                                            @elseif ($task['status'] === 'pending')
                                                <button type="button" class="training-primary-button" data-open-task>Realizar tarea</button>
                                            @else
                                                <button type="button" class="training-secondary-button" disabled>No disponible</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr data-task-empty-row hidden>
                                    <td colspan="4">No hay tareas en este estado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="training-information-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <p><strong>Ten en cuenta:</strong> las tareas de modulos futuros se desbloquean conforme progresas.</p>
                    </div>
                </section>
            </div>

            <div id="learner-panel-results" class="training-learner-view" data-learner-panel="results"
                role="tabpanel" aria-labelledby="learner-tab-results" hidden>
                <section class="training-results-section">
                    <header class="training-learner-section-heading">
                        <div>
                            <h2>Resultados</h2>
                            <p>Consulta tus calificaciones y el avance desbloqueado.</p>
                        </div>
                    </header>

                    <article class="training-result-hero" data-result-hero>
                        <div class="training-score-ring" style="--score: 88" data-result-ring>
                            <strong data-result-score>88%</strong>
                        </div>
                        <div class="training-result-summary">
                            <span class="training-state-pill is-approved" data-result-status><i class="fa-solid fa-check" aria-hidden="true"></i> Modulo aprobado</span>
                            <h3 data-result-module>Bienvenida e induccion</h3>
                            <p data-result-message-primary>Superaste el minimo aprobatorio de 80%.</p>
                            <p data-result-message-secondary>El modulo 2 ya esta disponible.</p>
                        </div>
                        <div class="training-result-actions">
                            <button type="button" class="training-primary-button" data-result-primary-action>Continuar modulo 2</button>
                            <button type="button" class="training-secondary-button" data-review-results>Revisar respuestas</button>
                        </div>
                    </article>

                    <div class="training-result-grid">
                        <article class="training-result-card">
                            <h3>Desglose de evaluacion</h3>
                            <div class="training-score-breakdown">
                                <div><span>Comprension</span><div class="training-progress-track"><span style="width: 88%" data-result-breakdown-fill></span></div><strong data-result-breakdown-score>88%</strong></div>
                                <div><span>Aplicacion</span><div class="training-progress-track"><span style="width: 88%" data-result-breakdown-fill></span></div><strong data-result-breakdown-score>88%</strong></div>
                                <div><span>Criterios</span><div class="training-progress-track"><span style="width: 88%" data-result-breakdown-fill></span></div><strong data-result-breakdown-score>88%</strong></div>
                            </div>
                            <div class="training-result-metrics">
                                <div><i class="fa-regular fa-clipboard" aria-hidden="true"></i><span>Intento<strong data-result-attempt>1</strong></span></div>
                                <div><i class="fa-regular fa-clock" aria-hidden="true"></i><span>Estado<strong data-result-pass-state>Aprobado</strong></span></div>
                                <div><i class="fa-regular fa-circle-check" aria-hidden="true"></i><span>Correctas<strong data-result-correct-answers>3/3</strong></span></div>
                            </div>
                        </article>

                        <article class="training-result-card">
                            <h3>Historial por modulo</h3>
                            <div class="training-result-history" data-result-history></div>
                        </article>
                    </div>
                </section>
            </div>

            <div id="learner-panel-certificates" class="training-learner-view" data-learner-panel="certificates"
                role="tabpanel" aria-labelledby="learner-tab-certificates" hidden>
                <section class="training-learner-section">
                    <header class="training-learner-section-heading">
                        <div>
                            <h2>Certificados</h2>
                            <p>Consulta la disponibilidad de tus constancias de capacitacion.</p>
                        </div>
                    </header>

                    <article class="training-certificate-card" data-certificate-card>
                        <div class="training-certificate-icon"><i class="fa-solid fa-award" aria-hidden="true"></i></div>
                        <div class="training-certificate-content">
                            <span>Programa obligatorio</span>
                            <h3>Induccion y seguridad operativa</h3>
                            <p data-certificate-message>Completa los cuatro modulos y la evaluacion final para emitir el certificado.</p>
                            <div class="training-certificate-progress">
                                <div class="training-progress-track" aria-label="Progreso del certificado: 25%" data-certificate-progress-track><span style="width: 25%" data-certificate-progress-fill></span></div>
                                <strong data-certificate-progress-label>1 de 4 modulos</strong>
                            </div>
                        </div>
                        <div class="training-certificate-actions">
                            <span class="training-state-pill is-locked" data-certificate-status><i class="fa-solid fa-lock" aria-hidden="true"></i> Pendiente</span>
                            <button type="button" class="training-secondary-button" data-certificate-action>Ver requisitos</button>
                        </div>
                    </article>

                    <div class="training-information-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <p>El certificado se habilitara automaticamente cuando completes y apruebes todo el programa.</p>
                    </div>
                </section>
            </div>

            <dialog class="training-learner-dialog training-video-dialog" data-learner-video-dialog
                aria-labelledby="learner-video-dialog-title">
                <div class="training-learner-dialog-shell">
                    <header>
                        <div>
                            <span data-current-module-label>Modulo 2</span>
                            <h3 id="learner-video-dialog-title" data-current-module-title>Seguridad operativa</h3>
                            <p><span data-current-chapter-label>Capitulo 1 de 2</span> &middot; <b data-current-module-progress>33% del modulo</b></p>
                        </div>
                        <button type="button" class="training-icon-button" data-close-learner-video
                            title="Cerrar video" aria-label="Cerrar video">
                            <span class="training-close-glyph" aria-hidden="true">&times;</span>
                        </button>
                    </header>

                    <article class="training-video-panel" data-current-content-panel>
                        <button type="button" class="training-video-frame" aria-label="Reproducir video de capacitacion"
                            data-training-video-play>
                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                            <strong data-current-video-title>Buenas practicas en area esteril</strong>
                            <span data-video-playback-status>Listo para reproducir</span>
                            <span class="training-video-playback-track" aria-hidden="true">
                                <span data-video-playback-fill></span>
                            </span>
                        </button>

                        <div class="training-chapter-detail">
                            <div>
                                <span>Video de capacitacion</span>
                                <h4 data-current-chapter-title>Prevencion y proteccion</h4>
                                <p data-current-video-description>Reconoce los puntos criticos antes de ingresar y trabajar en el area.</p>
                            </div>
                            <button type="button" class="training-primary-button" data-complete-training-video disabled>
                                Finalizar video y continuar
                            </button>
                        </div>
                    </article>
                </div>
            </dialog>

            <dialog class="training-learner-dialog" data-task-dialog aria-labelledby="learner-task-dialog-title">
                <div class="training-learner-dialog-shell">
                    <header>
                        <div>
                            <span data-task-dialog-eyebrow>Tarea del modulo</span>
                            <h3 id="learner-task-dialog-title" data-task-dialog-title>Realizar tarea</h3>
                            <p data-task-dialog-module></p>
                        </div>
                        <button type="button" class="training-icon-button" data-close-task-dialog aria-label="Cerrar tarea">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <form data-task-submission-form>
                        <input type="hidden" name="task_id" data-task-dialog-id>
                        <div class="training-task-instructions">
                            <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                            <p>Describe los riesgos identificados y las medidas preventivas que aplicarias en el area esteril.</p>
                        </div>
                        <label>
                            <span>Respuesta <b>*</b></span>
                            <textarea name="response" rows="6" required data-task-response placeholder="Escribe aqui tu respuesta..."></textarea>
                        </label>
                        <label class="training-task-file-field">
                            <span>Evidencia adjunta</span>
                            <input type="file" name="evidence" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" data-task-evidence>
                            <small>PDF, Word o imagen. Maximo 10 MB.</small>
                        </label>
                        <footer>
                            <button type="button" class="training-secondary-button" data-close-task-dialog>Cancelar</button>
                            <button type="submit" class="training-primary-button"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar tarea</button>
                        </footer>
                    </form>

                    <div class="training-task-submission-view" data-task-submission-view hidden>
                        <div>
                            <span>Respuesta enviada</span>
                            <p data-task-submitted-response></p>
                        </div>
                        <div>
                            <span>Evidencia</span>
                            <p data-task-submitted-file>Sin archivo adjunto</p>
                        </div>
                        <footer>
                            <button type="button" class="training-primary-button" data-close-task-dialog>Cerrar</button>
                        </footer>
                    </div>
                </div>
            </dialog>

            <dialog class="training-learner-dialog training-exam-dialog" data-learner-exam-dialog
                aria-labelledby="learner-exam-dialog-title">
                <div class="training-learner-dialog-shell">
                    <header>
                        <div>
                            <span>Evaluacion obligatoria</span>
                            <h3 id="learner-exam-dialog-title" data-learner-exam-title>Examen del modulo</h3>
                            <p><span data-learner-exam-module></span> &middot; Minimo aprobatorio <strong data-learner-exam-minimum>80%</strong></p>
                        </div>
                        <button type="button" class="training-icon-button" data-close-learner-exam aria-label="Cerrar examen">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <form data-learner-exam-form>
                        <div class="training-exam-intro">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            <p>Responde todas las preguntas. Debes alcanzar el minimo indicado para habilitar el siguiente modulo.</p>
                        </div>
                        <div class="training-learner-exam-questions" data-learner-exam-questions></div>
                        <p class="training-exam-validation" data-learner-exam-validation hidden>Responde todas las preguntas antes de continuar.</p>
                        <footer>
                            <button type="button" class="training-secondary-button" data-close-learner-exam>Cancelar</button>
                            <button type="submit" class="training-primary-button">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                Calificar examen
                            </button>
                        </footer>
                    </form>
                </div>
            </dialog>

            <dialog class="training-learner-dialog" data-result-review-dialog aria-labelledby="result-review-title">
                <div class="training-learner-dialog-shell">
                    <header>
                        <div>
                            <span data-review-result-eyebrow>Resultado del modulo</span>
                            <h3 id="result-review-title">Revision de respuestas</h3>
                            <p data-review-result-summary></p>
                        </div>
                        <button type="button" class="training-icon-button" data-close-result-review aria-label="Cerrar revision">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>
                    <div class="training-answer-review-list" data-review-result-list></div>
                    <footer>
                        <button type="button" class="training-primary-button" data-close-result-review>Cerrar revision</button>
                    </footer>
                </div>
            </dialog>
        </section>
    </div>

    @push('css')
        <style>
            .training-screen {
                --training-ink: #102a43;
                --training-soft: #486581;
                --training-line: #d9e2ec;
                --training-blue: #1d70d8;
                --training-navy: #2f3f82;
                --training-teal: #0fa98f;
                --training-teal-dark: #087f73;
                --training-mint: #e4fbf4;
                --training-shadow: 0 14px 30px rgba(16, 42, 67, 0.08);
                display: grid;
                gap: 1.25rem;
                color: var(--training-ink);
            }

            .training-page-header,
            .training-section-heading,
            .training-actions,
            .training-pagination,
            .training-person,
            .training-video-heading,
            .training-chapter-detail,
            .training-route-heading {
                display: flex;
                align-items: center;
            }

            .training-page-header {
                justify-content: space-between;
                gap: 1rem;
            }

            .training-page-heading {
                display: grid;
                min-width: 0;
                gap: 0.65rem;
            }

            .training-header-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.6rem;
            }

            .training-personnel-actions {
                margin-top: 0.75rem;
            }

            .training-page-header h1 {
                margin: 0;
                color: #0f172a;
                font-size: 1.75rem;
                font-weight: 750;
                letter-spacing: 0;
            }

            .training-page-header p,
            .training-learner-hero p {
                margin: 0.25rem 0 0;
                color: var(--training-soft);
                font-size: 0.92rem;
            }

            .training-role-switch {
                display: grid;
                grid-template-columns: repeat(2, minmax(7rem, 1fr));
                gap: 0.35rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.25rem;
                background: #ffffff;
            }

            .training-role-switch button,
            .training-tabs button,
            .training-filter-button,
            .training-primary-button,
            .training-secondary-button,
            .training-icon-button,
            .training-route-step {
                border-radius: 8px;
                font-weight: 750;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .training-role-switch button {
                min-height: 2.35rem;
                border: 1px solid transparent;
                color: #31516f;
                background: transparent;
            }

            .training-role-switch button.is-active {
                border-color: #b8c9e8;
                color: var(--training-navy);
                background: #eef5ff;
                box-shadow: 0 6px 16px rgba(47, 63, 130, 0.12);
            }

            .training-tabs {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                overflow: hidden;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
            }

            .training-tabs button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.65rem;
                min-height: 3rem;
                border: 0;
                border-bottom: 2px solid transparent;
                color: #52687f;
                background: #ffffff;
                font-size: 0.9rem;
            }

            .training-tabs button.is-active {
                border-bottom-color: var(--training-blue);
                color: #1958bb;
                background: #fbfdff;
            }

            .training-tabs button:focus,
            .training-task-filters button:focus {
                outline: 0;
            }

            .training-tabs button:focus-visible,
            .training-task-filters button:focus-visible,
            .training-program-carousel-arrow:focus-visible,
            .training-program-new-tile:focus-visible,
            .training-program-carousel-select:focus-visible,
            .training-program-carousel-edit:focus-visible {
                outline: 2px solid rgba(29, 112, 216, 0.42);
                outline-offset: -3px;
            }

            .training-tabs.is-programs {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .training-program-carousel {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                margin: 1rem 0 0.9rem;
            }

            .training-program-carousel-viewport {
                min-width: 0;
                flex: 1;
                overflow-x: auto;
                padding: 0.15rem 0 0.4rem;
                scroll-behavior: smooth;
                scroll-snap-type: x proximity;
                scrollbar-color: #aebed2 transparent;
                scrollbar-width: thin;
            }

            .training-program-carousel-track {
                display: flex;
                width: max-content;
                min-width: 100%;
                gap: 0.7rem;
            }

            .training-program-carousel-arrow {
                display: grid;
                width: 2.35rem;
                height: 2.35rem;
                flex: 0 0 2.35rem;
                place-items: center;
                border: 1px solid #c8d7e8;
                border-radius: 999px;
                color: #174c97;
                background: #ffffff;
                cursor: pointer;
                box-shadow: 0 6px 14px rgba(16, 42, 67, 0.08);
                font-size: 1.35rem;
                line-height: 1;
            }

            .training-program-carousel-arrow:hover:not(:disabled) {
                border-color: #73a9ed;
                background: #eef6ff;
            }

            .training-program-carousel-arrow:disabled {
                color: #9eacbc;
                background: #f5f7fa;
                cursor: not-allowed;
                opacity: 0.65;
            }

            .training-program-new-tile,
            .training-program-carousel-card {
                flex: 0 0 18rem;
                min-height: 9rem;
                scroll-snap-align: start;
                border-radius: 8px;
            }

            .training-program-new-tile {
                display: flex;
                flex-basis: 11rem;
                align-items: center;
                justify-content: center;
                gap: 0.7rem;
                border: 1px dashed #15a889;
                padding: 0.9rem;
                color: #087b67;
                background: #f1fcf8;
                cursor: pointer;
                text-align: left;
            }

            .training-program-new-tile:hover {
                border-style: solid;
                background: #e4f9f2;
            }

            .training-program-new-icon,
            .training-program-carousel-icon {
                display: grid;
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 auto;
                place-items: center;
                border-radius: 8px;
                color: #0b6f5d;
                background: #d8f6ec;
            }

            .training-program-new-tile span:last-child,
            .training-program-carousel-meta span {
                display: grid;
                gap: 0.12rem;
            }

            .training-program-new-tile strong {
                font-size: 0.88rem;
            }

            .training-program-new-tile small {
                color: #4c6b68;
                font-size: 0.7rem;
            }

            .training-program-carousel-card {
                position: relative;
                overflow: hidden;
                border: 1px solid #d5dfeb;
                background: #ffffff;
                transition: border-color 160ms ease, background-color 160ms ease, box-shadow 160ms ease;
            }

            .training-program-carousel-card.is-active {
                border-color: #2c8de0;
                background: #eef8ff;
                box-shadow: inset 0 -3px 0 #1e70d8, 0 6px 16px rgba(18, 78, 145, 0.1);
            }

            .training-program-carousel-select {
                display: grid;
                width: 100%;
                min-height: 9rem;
                gap: 0.45rem;
                border: 0;
                padding: 0.75rem 3rem 0.75rem 0.8rem;
                color: var(--training-ink);
                background: transparent;
                cursor: pointer;
                text-align: left;
            }

            .training-program-carousel-card-header,
            .training-program-carousel-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.6rem;
            }

            .training-program-carousel-select > strong {
                display: -webkit-box;
                overflow: hidden;
                min-height: 2.15rem;
                font-size: 0.86rem;
                line-height: 1.25;
                white-space: normal;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .training-program-carousel-description {
                display: -webkit-box;
                overflow: hidden;
                color: var(--training-soft);
                font-size: 0.7rem;
                line-height: 1.35;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .training-program-carousel-meta {
                align-items: end;
                padding-top: 0.25rem;
            }

            .training-program-carousel-meta small {
                color: var(--training-soft);
                font-size: 0.62rem;
            }

            .training-program-carousel-meta b {
                overflow: hidden;
                max-width: 8rem;
                font-size: 0.7rem;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-program-carousel-edit {
                position: absolute;
                right: 0.65rem;
                bottom: 0.65rem;
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border: 1px solid #b9cce4;
                border-radius: 6px;
                color: #174c97;
                background: #ffffff;
                cursor: pointer;
            }

            .training-program-carousel-edit:hover {
                border-color: #70a6e9;
                background: #eaf3ff;
            }

            .training-program-edit-glyph {
                display: block;
                font-family: "Segoe UI Symbol", sans-serif;
                font-size: 1rem;
                line-height: 1;
                transform: translateY(-1px);
            }

            .training-program-context {
                display: flex;
                align-items: end;
                margin-top: 0.8rem;
            }

            .training-program-context[hidden] {
                display: none;
            }

            .training-program-context label {
                display: grid;
                width: min(30rem, 100%);
                gap: 0.35rem;
            }

            .training-program-context label > span {
                color: var(--training-soft);
                font-size: 0.75rem;
                font-weight: 750;
            }

            .training-program-context select {
                width: 100%;
                min-height: 2.5rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                appearance: none;
                cursor: pointer;
                padding-right: 3rem;
                font-size: 0.88rem;
                font-weight: 700;
            }

            .training-program-select-control {
                position: relative;
                display: block;
                width: 100%;
            }

            .training-program-select-icon {
                position: absolute;
                top: 1px;
                right: 1px;
                bottom: 1px;
                display: flex;
                width: 2.5rem;
                align-items: center;
                justify-content: center;
                border-left: 1px solid var(--training-line);
                border-radius: 0 7px 7px 0;
                color: #1e4fa3;
                background: #f8fafc;
                pointer-events: none;
                font-size: 0.75rem;
            }

            .training-program-context select:focus + .training-program-select-icon {
                color: #0f3f91;
                background: #eef5ff;
            }

            [data-program-panel][hidden] {
                display: none;
            }

            .training-section-heading > div > p {
                margin: 0.25rem 0 0;
                color: var(--training-soft);
                font-size: 0.82rem;
            }

            .training-exam-save-feedback {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                margin-top: 0.85rem;
                border: 1px solid #9adfc8;
                border-radius: 6px;
                padding: 0.7rem 0.85rem;
                color: #08634b;
                background: #eefbf6;
                font-size: 0.84rem;
                font-weight: 700;
            }

            .training-exam-save-feedback[hidden] {
                display: none;
            }

            .training-project-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.9rem;
                margin-top: 0.9rem;
            }

            .training-project-card {
                display: grid;
                gap: 0.85rem;
                min-width: 0;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1rem;
                background: #ffffff;
                box-shadow: var(--training-shadow);
            }

            .training-project-card-header,
            .training-module-list-heading,
            .training-module-editor-heading,
            .training-chapter-heading,
            .training-chapter-row-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .training-project-icon,
            .training-module-symbol {
                display: grid;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 auto;
                place-items: center;
                border-radius: 8px;
                color: #1958bb;
                background: #eaf2ff;
            }

            .training-project-card h3 {
                min-height: 2.7rem;
                margin: 0;
                color: #172b4d;
                font-size: 1.08rem;
                font-weight: 800;
                line-height: 1.3;
            }

            .training-project-card > p {
                min-height: 3rem;
                margin: 0;
                color: var(--training-soft);
                font-size: 0.83rem;
                line-height: 1.5;
            }

            .training-project-card dl {
                display: grid;
                grid-template-columns: minmax(0, 1.2fr) repeat(2, minmax(4rem, 0.55fr));
                gap: 0.6rem;
                margin: 0;
                border-block: 1px solid #e7edf5;
                padding-block: 0.75rem;
            }

            .training-project-card dt {
                color: var(--training-soft);
                font-size: 0.68rem;
                font-weight: 750;
            }

            .training-project-card dd {
                overflow: hidden;
                margin: 0.2rem 0 0;
                color: var(--training-ink);
                font-size: 0.82rem;
                font-weight: 800;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-card-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
            }

            .training-card-actions button {
                width: 100%;
                min-width: 0;
            }

            .training-edit-button {
                display: inline-flex;
                min-height: 2.5rem;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                border: 1px solid var(--training-navy);
                border-radius: 8px;
                padding: 0 0.8rem;
                color: #ffffff;
                background: var(--training-navy);
                font-weight: 750;
                transition: background-color 160ms ease, border-color 160ms ease;
            }

            .training-edit-button:hover {
                border-color: #24336f;
                background: #24336f;
            }

            .training-program-modal {
                width: min(42rem, calc(100% - 2rem));
                max-height: calc(100vh - 2rem);
                margin: auto;
                overflow: auto;
                border: 0;
                border-radius: 8px;
                padding: 0;
                color: var(--training-ink);
                background: #ffffff;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            }

            .training-program-modal::backdrop {
                background: rgba(15, 23, 42, 0.48);
            }

            .training-program-modal form {
                display: grid;
            }

            .training-program-modal header,
            .training-program-modal footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 1rem 1.15rem;
            }

            .training-program-modal header {
                border-bottom: 1px solid var(--training-line);
            }

            .training-program-modal header span {
                color: var(--training-soft);
                font-size: 0.72rem;
                font-weight: 750;
            }

            .training-program-modal header h3 {
                margin: 0.2rem 0 0;
                font-size: 1.2rem;
                font-weight: 800;
            }

            .training-modal-close {
                display: grid;
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 auto;
                place-items: center;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: #52687f;
                background: #ffffff;
            }

            .training-modal-close:hover {
                color: var(--training-ink);
                background: #f2f6fb;
            }

            .training-program-modal-body {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.9rem;
                padding: 1.15rem;
            }

            .training-program-modal-body .is-wide {
                grid-column: 1 / -1;
            }

            .training-program-modal-body label {
                display: grid;
                min-width: 0;
                gap: 0.35rem;
                color: var(--training-soft);
                font-size: 0.75rem;
                font-weight: 750;
            }

            .training-assignment-modal {
                width: min(52rem, calc(100% - 2rem));
            }

            .training-assignment-modal header p {
                margin: 0.35rem 0 0;
                color: var(--training-soft);
                font-size: 0.82rem;
            }

            .training-assignment-modal header p strong {
                color: var(--training-ink);
            }

            .training-assignment-student-picker {
                display: grid;
                gap: 0.4rem;
                color: var(--training-soft);
                font-size: 0.78rem;
                font-weight: 750;
            }

            .training-assignment-student-picker[hidden] {
                display: none;
            }

            .training-assignment-student-picker b {
                color: #d14343;
            }

            .training-assignment-start-date {
                display: grid;
                width: min(100%, 14rem);
                gap: 0.4rem;
                color: var(--training-soft);
                font-size: 0.78rem;
                font-weight: 750;
            }

            .training-assignment-start-date b {
                color: #d14343;
            }

            .training-assignment-start-date input {
                width: 100%;
                min-height: 2.65rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.65rem 0.8rem;
                color: var(--training-ink);
                background: #ffffff;
                font: inherit;
            }

            .training-assignment-start-date input:focus {
                border-color: #67c8d8;
                outline: 2px solid rgba(6, 182, 212, 0.14);
            }

            .training-assignment-student-control {
                position: relative;
                display: block;
            }

            .training-assignment-student-control select {
                width: 100%;
                min-height: 2.65rem;
                appearance: none;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0 2.5rem 0 0.8rem;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.86rem;
            }

            .training-assignment-student-control select:focus {
                border-color: #67c8d8;
                outline: 2px solid rgba(6, 182, 212, 0.14);
            }

            .training-assignment-student-control i {
                position: absolute;
                top: 50%;
                right: 0.85rem;
                color: var(--training-soft);
                pointer-events: none;
                transform: translateY(-50%);
            }

            .training-assignment-body {
                display: grid;
                gap: 1rem;
                padding: 1.15rem;
                background: #f8fafc;
            }

            .training-assignment-select-all,
            .training-assignment-program-choice,
            .training-assignment-module-list label {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                cursor: pointer;
            }

            .training-assignment-select-all {
                align-items: center;
                border: 1px solid #b8c9e8;
                border-radius: 8px;
                padding: 0.85rem 1rem;
                color: var(--training-navy);
                background: #eef5ff;
            }

            .training-assignment-select-all input,
            .training-assignment-program-choice input,
            .training-assignment-module-list input {
                width: 1rem;
                height: 1rem;
                flex: 0 0 auto;
                margin-top: 0.12rem;
                accent-color: var(--training-teal);
            }

            .training-assignment-select-all span,
            .training-assignment-program-choice span,
            .training-assignment-module-list span {
                display: grid;
                min-width: 0;
                gap: 0.18rem;
            }

            .training-assignment-select-all small,
            .training-assignment-program-choice small,
            .training-assignment-module-list small {
                color: var(--training-soft);
                font-size: 0.75rem;
                line-height: 1.35;
            }

            .training-assignment-program-list {
                display: grid;
                gap: 0.75rem;
            }

            .training-assignment-program {
                overflow: hidden;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
            }

            .training-assignment-program-choice {
                align-items: center;
                padding: 0.85rem 1rem;
                background: #f8fafc;
            }

            .training-assignment-program-choice:has(input:checked),
            .training-assignment-program-choice:has(input:indeterminate) {
                color: var(--training-teal-dark);
                background: #effcf8;
            }

            .training-assignment-module-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.55rem;
                border-top: 1px solid var(--training-line);
                padding: 0.85rem 1rem 1rem 2.75rem;
            }

            .training-assignment-module-list label {
                min-width: 0;
                border: 1px solid transparent;
                border-radius: 8px;
                padding: 0.55rem 0.65rem;
            }

            .training-assignment-module-list label:has(input:checked) {
                border-color: rgba(15, 169, 143, 0.4);
                background: #f2fffb;
            }

            .training-assignment-count {
                color: var(--training-soft);
                font-size: 0.82rem;
                font-weight: 750;
            }

            .training-assignment-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.65rem;
            }

            .training-primary-button:disabled {
                cursor: not-allowed;
                opacity: 0.5;
            }

            .training-program-modal-body input,
            .training-program-modal-body select,
            .training-program-modal-body textarea {
                width: 100%;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.88rem;
            }

            .training-program-modal-body input,
            .training-program-modal-body select {
                height: 2.55rem;
            }

            .training-program-modal-body textarea {
                min-height: 6.5rem;
                resize: vertical;
            }

            .training-program-modal footer {
                justify-content: flex-end;
                border-top: 1px solid var(--training-line);
                background: #fbfdff;
            }

            .training-exam-modal {
                width: min(64rem, calc(100% - 2rem));
            }

            .training-exam-editor-body,
            .training-exam-preview {
                display: grid;
                gap: 1rem;
                padding: 1.15rem;
            }

            .training-exam-meta-fields {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(11rem, 0.32fr);
                gap: 0.9rem;
            }

            .training-exam-meta-fields label,
            .training-exam-question-field {
                display: grid;
                min-width: 0;
                gap: 0.35rem;
                color: var(--training-soft);
                font-size: 0.75rem;
                font-weight: 750;
            }

            .training-exam-meta-fields input,
            .training-exam-question-field textarea,
            .training-exam-option-row input[type="text"] {
                width: 100%;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.88rem;
            }

            .training-exam-meta-fields input,
            .training-exam-option-row input[type="text"] {
                height: 2.55rem;
            }

            .training-exam-question-field textarea {
                min-height: 4.5rem;
                resize: vertical;
            }

            .training-exam-meta-fields input:focus,
            .training-exam-question-field textarea:focus,
            .training-exam-option-row input[type="text"]:focus {
                border-color: #7ba8e8;
                outline: 2px solid rgba(29, 112, 216, 0.12);
            }

            .training-percent-field {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 2.7rem;
                align-items: center;
                overflow: hidden;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #f7f9fc;
            }

            .training-percent-field input {
                border: 0;
                border-radius: 0;
            }

            .training-percent-field strong {
                color: var(--training-soft);
                text-align: center;
            }

            .training-exam-questions-heading,
            .training-exam-question-heading,
            .training-exam-preview-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .training-exam-questions-heading {
                padding-top: 0.9rem;
                border-top: 1px solid var(--training-line);
            }

            .training-exam-questions-heading h4,
            .training-exam-question-heading strong,
            .training-exam-preview-heading h4 {
                margin: 0;
            }

            .training-exam-questions-heading span,
            .training-exam-preview-heading span {
                color: var(--training-soft);
                font-size: 0.76rem;
            }

            .training-exam-question-list,
            .training-exam-preview-list {
                display: grid;
                gap: 0.8rem;
            }

            .training-exam-question-editor,
            .training-exam-preview-question {
                display: grid;
                gap: 0.8rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.9rem;
                background: #ffffff;
            }

            .training-exam-question-editor {
                background: #fbfdff;
            }

            .training-exam-options {
                display: grid;
                gap: 0.5rem;
            }

            .training-exam-options-label {
                color: var(--training-soft);
                font-size: 0.75rem;
                font-weight: 750;
            }

            .training-exam-option-row {
                display: grid;
                grid-template-columns: 1.8rem minmax(0, 1fr) 2rem;
                align-items: center;
                gap: 0.45rem;
            }

            .training-exam-option-row input[type="radio"],
            .training-exam-preview-option input {
                width: 1rem;
                height: 1rem;
                accent-color: var(--training-teal);
            }

            .training-exam-option-row .training-icon-button {
                color: #7b8794;
            }

            .training-exam-option-row .training-icon-button:disabled,
            .training-exam-question-heading .training-icon-button:disabled {
                cursor: not-allowed;
                opacity: 0.35;
            }

            .training-exam-add-option {
                justify-self: start;
            }

            .training-exam-preview-heading {
                align-items: flex-end;
                padding-bottom: 0.9rem;
                border-bottom: 1px solid var(--training-line);
            }

            .training-exam-preview-heading p {
                margin: 0.25rem 0 0;
                color: var(--training-soft);
                font-size: 0.8rem;
            }

            .training-exam-preview-instructions {
                margin: 0;
                border-left: 3px solid #6ca6e8;
                padding: 0.7rem 0.85rem;
                color: #41566f;
                background: #f4f8ff;
                font-size: 0.82rem;
            }

            .training-exam-preview-question fieldset {
                display: grid;
                gap: 0.55rem;
                margin: 0;
                border: 0;
                padding: 0;
            }

            .training-exam-preview-question legend {
                margin-bottom: 0.7rem;
                color: var(--training-ink);
                font-size: 0.92rem;
                font-weight: 800;
            }

            .training-exam-preview-option {
                display: grid;
                grid-template-columns: 1.3rem minmax(0, 1fr);
                align-items: center;
                gap: 0.45rem;
                border: 1px solid #e3eaf3;
                border-radius: 8px;
                padding: 0.7rem;
                color: #41566f;
                background: #fbfdff;
            }

            .training-exam-builder {
                display: grid;
                gap: 1rem;
            }

            .training-exam-builder[hidden] {
                display: none;
            }

            .training-exam-builder-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .training-exam-builder-header p {
                margin: 0 0 0.35rem;
                color: #1d5fcc;
                font-size: 0.76rem;
                font-weight: 800;
            }

            .training-exam-builder-header h2 {
                margin: 0;
                font-size: 1.45rem;
            }

            .training-exam-builder-header > div > span {
                display: block;
                margin-top: 0.25rem;
                color: var(--training-soft);
                font-size: 0.82rem;
            }

            .training-exam-builder form {
                display: grid;
                gap: 1rem;
            }

            .training-exam-builder-section {
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1rem;
                background: #ffffff;
            }

            .training-exam-builder-section > h3,
            .training-exam-builder-question-heading h3 {
                margin: 0;
                font-size: 1rem;
                font-weight: 850;
            }

            .training-exam-general-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.9rem;
                margin-top: 0.8rem;
            }

            .training-exam-general-grid label,
            .training-new-question-field,
            .training-new-question-toolbar label {
                display: grid;
                min-width: 0;
                gap: 0.35rem;
                color: var(--training-soft);
                font-size: 0.75rem;
                font-weight: 750;
            }

            .training-exam-general-grid .is-wide {
                grid-column: span 2;
            }

            .training-exam-general-grid input,
            .training-exam-general-grid select,
            .training-exam-general-grid textarea,
            .training-new-question-field textarea,
            .training-new-question-toolbar select,
            .training-new-question-toolbar input,
            .training-new-question-option input[type="text"] {
                width: 100%;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.85rem;
            }

            .training-exam-general-grid input,
            .training-exam-general-grid select,
            .training-new-question-toolbar select,
            .training-new-question-toolbar input,
            .training-new-question-option input[type="text"] {
                height: 2.55rem;
            }

            .training-exam-general-grid textarea,
            .training-new-question-field textarea {
                min-height: 4.5rem;
                resize: vertical;
            }

            .training-exam-random-field {
                align-content: start;
            }

            .training-switch-control {
                position: relative;
                display: inline-flex;
                width: 2.7rem;
                height: 1.55rem;
                align-items: center;
            }

            .training-switch-control input {
                position: absolute;
                width: 1px;
                height: 1px;
                opacity: 0;
            }

            .training-switch-control > span {
                position: relative;
                width: 100%;
                height: 100%;
                border-radius: 999px;
                background: #cbd5e1;
                transition: background-color 160ms ease;
            }

            .training-switch-control > span::after {
                position: absolute;
                top: 0.2rem;
                left: 0.22rem;
                width: 1.15rem;
                height: 1.15rem;
                border-radius: 50%;
                background: #ffffff;
                box-shadow: 0 1px 4px rgba(15, 23, 42, 0.25);
                content: '';
                transition: transform 160ms ease;
            }

            .training-switch-control input:checked + span {
                background: var(--training-teal);
            }

            .training-switch-control input:checked + span::after {
                transform: translateX(1.08rem);
            }

            .training-switch-control input:focus-visible + span {
                outline: 2px solid #1d70d8;
                outline-offset: 2px;
            }

            .training-exam-builder-question-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.8rem;
            }

            .training-exam-builder-question-heading > div > span {
                display: block;
                margin-top: 0.25rem;
                color: var(--training-soft);
                font-size: 0.76rem;
            }

            .training-new-exam-question-list {
                display: grid;
                gap: 0.75rem;
                margin-top: 0.85rem;
            }

            .training-new-question-card {
                display: grid;
                gap: 0.85rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.85rem;
                background: #ffffff;
            }

            .training-new-question-toolbar {
                display: grid;
                grid-template-columns: auto auto auto minmax(11rem, 0.45fr) minmax(7rem, 0.2fr) 1fr;
                align-items: end;
                gap: 0.65rem;
            }

            .training-question-handle {
                align-self: center;
                color: #7a8da3;
            }

            .training-question-number {
                display: grid;
                width: 1.8rem;
                height: 1.8rem;
                align-self: center;
                place-items: center;
                border-radius: 999px;
                color: #ffffff;
                background: #1368d9;
                font-size: 0.75rem;
                font-weight: 850;
            }

            .training-question-toolbar-title {
                align-self: center;
                color: var(--training-ink);
                font-size: 0.86rem;
                font-weight: 850;
            }

            .training-question-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.25rem;
            }

            .training-new-question-option-list {
                display: grid;
                gap: 0.5rem;
            }

            .training-new-question-option {
                display: grid;
                grid-template-columns: 1.6rem minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.5rem;
                border: 1px solid #dbe4ef;
                border-radius: 8px;
                padding: 0.35rem 0.45rem;
                background: #ffffff;
            }

            .training-new-question-option.is-correct {
                border-color: #68d5a2;
                background: #ecfbf3;
            }

            .training-new-question-option input[type="radio"],
            .training-new-question-option input[type="checkbox"] {
                width: 1rem;
                height: 1rem;
                justify-self: center;
                accent-color: var(--training-teal);
            }

            .training-new-question-option input[type="text"] {
                border-color: transparent;
                background: transparent;
            }

            .training-new-question-option input[type="text"]:focus {
                border-color: #7ba8e8;
                outline: 2px solid rgba(29, 112, 216, 0.12);
                background: #ffffff;
            }

            .training-new-question-option small {
                color: #11824f;
                font-size: 0.7rem;
                font-weight: 800;
            }

            .training-new-question-option-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.35rem;
            }

            .training-new-question-add-option {
                justify-self: start;
            }

            .training-exam-builder-actions {
                position: sticky;
                z-index: 4;
                bottom: 0;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.7rem;
                border: 1px solid var(--training-line);
                border-radius: 8px 8px 0 0;
                padding: 0.8rem 1rem;
                background: rgba(255, 255, 255, 0.97);
                box-shadow: 0 -8px 22px rgba(15, 23, 42, 0.08);
            }

            .training-program-status {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                padding: 0.35rem 0.6rem;
                font-size: 0.72rem;
                font-weight: 800;
            }

            .training-program-status.is-active {
                color: #087f73;
                background: #ddf8ef;
            }

            .training-program-status.is-draft {
                color: #8a5a00;
                background: #fff0d9;
            }

            .training-program-status.is-inactive {
                color: #52687f;
                background: #edf2f7;
            }

            .training-module-workspace {
                display: grid;
                grid-template-columns: minmax(18rem, 0.78fr) minmax(0, 2.1fr);
                gap: 1rem;
                align-items: start;
                margin-top: 0.9rem;
            }

            .training-module-list,
            .training-module-editor {
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
            }

            .training-module-list {
                overflow: hidden;
            }

            .training-module-list-heading {
                padding: 0.9rem;
                border-bottom: 1px solid var(--training-line);
            }

            .training-module-list-heading span,
            .training-module-item small,
            .training-module-editor-heading span {
                color: var(--training-soft);
                font-size: 0.72rem;
                font-weight: 700;
            }

            .training-module-item {
                display: grid;
                width: 100%;
                grid-template-columns: auto minmax(0, 1fr) auto;
                gap: 0.7rem;
                align-items: center;
                border: 0;
                border-bottom: 1px solid #e7edf5;
                padding: 0.8rem;
                color: var(--training-ink);
                background: #ffffff;
                text-align: left;
            }

            .training-module-item:last-child {
                border-bottom: 0;
            }

            .training-module-item[hidden],
            [data-program-row][hidden] {
                display: none;
            }

            .training-module-item:hover,
            .training-module-item.is-active {
                background: #f1f7ff;
            }

            .training-module-item.is-active {
                box-shadow: inset 3px 0 0 var(--training-blue);
            }

            .training-module-item strong,
            .training-module-item small {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-module-item strong {
                font-size: 0.82rem;
            }

            .training-module-item small {
                margin-top: 0.25rem;
            }

            .training-module-item > i,
            .training-chapter-row > i {
                color: #8ca0b3;
            }

            .training-module-symbol {
                width: 2rem;
                height: 2rem;
                color: #087f73;
                background: #e4fbf4;
            }

            .training-module-editor {
                padding: 1rem;
            }

            .training-module-editor-heading {
                border-bottom: 1px solid var(--training-line);
                padding-bottom: 0.9rem;
            }

            .training-module-editor-heading h3 {
                margin: 0.2rem 0 0;
                font-size: 1.15rem;
                font-weight: 800;
            }

            .training-module-fields,
            .training-chapter-fields {
                display: grid;
                gap: 0.75rem;
            }

            .training-module-fields {
                grid-template-columns: minmax(9rem, 0.65fr) minmax(14rem, 1.35fr);
                margin-top: 0.9rem;
            }

            .training-module-fields .is-wide {
                grid-column: 1 / -1;
            }

            .training-module-fields label,
            .training-chapter-fields label {
                display: grid;
                min-width: 0;
                gap: 0.3rem;
                color: var(--training-soft);
                font-size: 0.72rem;
                font-weight: 750;
            }

            .training-module-fields input,
            .training-chapter-fields input:not([type="file"]) {
                width: 100%;
                min-width: 0;
                height: 2.4rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.82rem;
            }

            .training-chapter-fields input[readonly] {
                color: #41566f;
                background: #f4f7fb;
                cursor: default;
            }

            .training-chapter-heading {
                margin-top: 1rem;
                border-top: 1px solid var(--training-line);
                padding-top: 1rem;
            }

            .training-chapter-heading h4 {
                margin: 0;
                font-size: 1rem;
                font-weight: 800;
            }

            .training-chapter-list {
                margin-top: 0.75rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
            }

            .training-chapter-row {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                gap: 0.75rem;
                padding: 0.9rem;
                border-bottom: 1px solid var(--training-line);
            }

            .training-chapter-row:last-child {
                border-bottom: 0;
            }

            .training-chapter-body {
                min-width: 0;
            }

            .training-chapter-fields {
                grid-template-columns: minmax(10rem, 1fr) minmax(6rem, 0.45fr) minmax(12rem, 1.15fr) minmax(12rem, 1.2fr);
                margin-top: 0.65rem;
            }

            .training-upload-control {
                position: relative;
                grid-column: 1 / span 2;
                min-height: 4.25rem;
                align-content: center;
                border: 1px dashed var(--training-teal);
                border-radius: 8px;
                padding: 0.65rem 0.8rem;
                color: var(--training-teal-dark) !important;
                background: #f2fffb;
                cursor: pointer;
            }

            .training-upload-control input {
                position: absolute;
                width: 1px;
                height: 1px;
                opacity: 0;
            }

            .training-upload-control strong,
            .training-upload-control small {
                display: block;
            }

            .training-upload-control small {
                color: var(--training-soft);
                font-weight: 650;
                overflow-wrap: anywhere;
            }

            .training-upload-control.is-reading {
                border-color: #4c87d7;
                color: #245ea9 !important;
                background: #f2f7ff;
            }

            .training-upload-control.has-video {
                border-style: solid;
                background: #ebfbf4;
            }

            .training-upload-control.has-error {
                border-color: #df6a6a;
                color: #a23c3c !important;
                background: #fff5f5;
            }

            .training-program-table table {
                width: 100%;
                min-width: 64rem;
            }

            [data-program-empty-row] td {
                padding: 2rem 1rem;
                color: var(--training-soft);
                text-align: center;
            }

            .training-student-toolbar {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.65rem;
                margin-top: 0.2rem;
            }

            .training-new-assignment-button {
                min-height: 3rem;
                padding-inline: 1.15rem;
                font-weight: 800;
            }

            .training-student-view-switch {
                display: inline-grid;
                grid-template-columns: repeat(2, minmax(8.5rem, 1fr));
                gap: 0.55rem;
            }

            .training-student-view-switch a {
                display: inline-flex;
                min-height: 3rem;
                align-items: center;
                justify-content: center;
                gap: 0.55rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0 1rem;
                color: #31516f;
                background: #ffffff;
                font-weight: 750;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .training-student-view-switch a:hover {
                border-color: #67c8d8;
                color: var(--training-teal-dark);
                background: #f2fcfd;
            }

            .training-student-view-switch a.is-active {
                border-color: #06b6d4;
                color: #075985;
                background: #ecfeff;
                box-shadow: inset 0 -3px 0 #06b6d4;
            }

            .training-student-view-switch i {
                color: var(--training-teal-dark);
            }

            .training-laboratory-selector {
                margin-top: 1rem;
                border-bottom: 1px solid var(--training-line);
                padding-bottom: 1rem;
            }

            .training-laboratory-selector-heading {
                margin-bottom: 0.65rem;
            }

            .training-laboratory-selector-heading h2 {
                margin: 0;
                color: var(--training-ink);
                font-size: 0.9rem;
                font-weight: 850;
            }

            .training-laboratory-selector-heading p {
                margin: 0.15rem 0 0;
                color: var(--training-soft);
                font-size: 0.78rem;
            }

            .training-laboratory-carousel-shell {
                display: flex;
                align-items: center;
                gap: 0.7rem;
            }

            .training-laboratory-nav {
                display: grid;
                width: 2.35rem;
                height: 2.35rem;
                flex: 0 0 2.35rem;
                place-items: center;
                border: 1px solid var(--training-line);
                border-radius: 999px;
                color: #9aaabc;
                background: #ffffff;
                box-shadow: 0 6px 14px rgba(16, 42, 67, 0.08);
                font-size: 1.35rem;
                line-height: 1;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, opacity 160ms ease;
            }

            .training-laboratory-nav:hover:not(:disabled) {
                border-color: #9fb7d2;
                color: #31516f;
                background: #f8fbff;
            }

            .training-laboratory-nav:disabled {
                cursor: not-allowed;
                opacity: 0.45;
            }

            .training-laboratory-carousel {
                display: flex;
                min-width: 0;
                flex: 1;
                gap: 0.75rem;
                overflow-x: auto;
                padding: 0.1rem 0.1rem 0.55rem;
                scroll-behavior: smooth;
                scroll-snap-type: x mandatory;
            }

            .training-laboratory-card {
                display: grid;
                min-height: 7rem;
                flex: 0 0 16rem;
                align-content: space-between;
                gap: 0.7rem;
                border: 1px solid var(--training-line);
                border-radius: 4px;
                padding: 0.75rem;
                color: var(--training-ink);
                background: #ffffff;
                text-decoration: none;
                scroll-snap-align: start;
                transition: background-color 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
            }

            .training-laboratory-card:hover {
                border-color: #9fb7d2;
                background: #fbfdff;
            }

            .training-laboratory-card:focus-visible {
                outline: 2px solid #67e8f9;
                outline-offset: 2px;
            }

            .training-laboratory-card.is-active {
                border-color: #06b6d4;
                background: #ecfeff;
            }

            .training-laboratory-card-heading {
                display: flex;
                min-width: 0;
                gap: 0.65rem;
            }

            .training-laboratory-letter {
                display: grid;
                width: 2rem;
                height: 2rem;
                flex: 0 0 2rem;
                place-items: center;
                border-radius: 4px;
                color: #075985;
                background: #ffffff;
                font-size: 0.82rem;
                font-weight: 850;
                box-shadow: 0 1px 4px rgba(16, 42, 67, 0.08);
            }

            .training-laboratory-card-copy {
                display: grid;
                min-width: 0;
                gap: 0.15rem;
            }

            .training-laboratory-card-copy strong,
            .training-laboratory-card-copy small,
            .training-laboratory-card-copy em {
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .training-laboratory-card-copy strong {
                color: #0f172a;
                font-size: 0.88rem;
                font-weight: 850;
                white-space: nowrap;
            }

            .training-laboratory-card-copy small {
                color: #075985;
                font-size: 0.72rem;
                font-weight: 750;
                white-space: nowrap;
            }

            .training-laboratory-card-copy em {
                display: -webkit-box;
                color: var(--training-soft);
                font-size: 0.76rem;
                font-style: normal;
                line-height: 1.35;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .training-laboratory-card-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
                color: var(--training-soft);
                font-size: 0.76rem;
                font-weight: 750;
            }

            .training-laboratory-card-meta > span {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                min-width: 0;
            }

            .training-laboratory-card-meta > span:last-child {
                justify-content: flex-end;
                text-align: right;
            }

            .training-laboratory-card-meta i {
                width: 0.45rem;
                height: 0.45rem;
                flex: 0 0 0.45rem;
                border-radius: 999px;
                background: var(--training-teal);
            }

            .training-laboratory-card-meta > span:first-child {
                color: var(--training-teal-dark);
            }

            .training-laboratory-empty {
                border: 1px solid #f5d28a;
                border-radius: 4px;
                padding: 0.75rem 1rem;
                color: #8a5a00;
                background: #fff7e6;
                font-size: 0.85rem;
            }

            .training-section-heading {
                justify-content: space-between;
                gap: 1rem;
                margin-top: 1.25rem;
            }

            .training-section-heading h2 {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 750;
            }

            .training-actions {
                gap: 0.7rem;
                flex-wrap: wrap;
            }

            .training-primary-button,
            .training-secondary-button,
            .training-filter-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                min-height: 2.5rem;
                padding: 0 1rem;
            }

            .training-primary-button {
                border: 0;
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-primary-button:hover {
                background: var(--training-teal-dark);
            }

            .training-secondary-button {
                border: 1px solid var(--training-teal);
                color: var(--training-teal-dark);
                background: #ffffff;
            }

            .training-filter-button {
                align-self: end;
                border: 1px solid var(--training-line);
                color: #52687f;
                background: #ffffff;
            }

            .training-learner-metrics article,
            .training-video-panel,
            .training-route-panel {
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
                box-shadow: var(--training-shadow);
            }

            .training-filters label span,
            .training-learner-metrics span,
            .training-learner-hero span,
            .training-video-heading span,
            .training-chapter-detail span,
            .training-route-heading span {
                color: var(--training-soft);
                font-size: 0.78rem;
                font-weight: 750;
            }

            .training-filters {
                display: grid;
                grid-template-columns: minmax(14rem, 0.9fr) minmax(14rem, 0.7fr) auto;
                gap: 0.9rem;
                align-items: end;
                margin-top: 0.9rem;
            }

            .training-filters.is-personnel {
                grid-template-columns: minmax(12rem, 20rem) auto;
                justify-content: start;
                margin-top: 0.75rem;
            }

            .training-filters label {
                display: grid;
                gap: 0.35rem;
            }

            .training-filters input,
            .training-filters select {
                min-height: 2.45rem;
                width: 100%;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: var(--training-ink);
                background: #ffffff;
                font-size: 0.9rem;
            }

            .training-input-icon {
                position: relative;
            }

            .training-input-icon i {
                position: absolute;
                top: 50%;
                left: 0.75rem;
                color: #9aaabc;
                transform: translateY(-50%);
            }

            .training-input-icon input {
                padding-left: 2.2rem;
            }

            .training-table {
                overflow-x: auto;
                margin-top: 0.9rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
            }

            .training-table table {
                min-width: 58rem;
                border-collapse: collapse;
            }

            .training-personnel-table table {
                min-width: 250rem;
            }

            .training-personnel-table thead th[rowspan="2"] {
                vertical-align: middle;
            }

            .training-user-group {
                border-left: 1px solid #cbdcf4;
                border-right: 1px solid #cbdcf4;
                text-align: center !important;
                letter-spacing: 0;
            }

            .training-user-group i {
                margin-right: 0.35rem;
            }

            .training-user-group.is-software,
            .training-user-subheading th.is-software {
                color: #234b86;
                background: #eaf2ff;
            }

            .training-user-group.is-training,
            .training-user-subheading th.is-training {
                color: #087f73;
                background: #e8fbf7;
            }

            .training-user-subheading th {
                min-width: 16rem;
                border-top: 1px solid #dce6f3;
            }

            .training-user-access-cell {
                min-width: 16rem;
                border-left: 1px solid #e0e8f2;
                border-right: 1px solid #e0e8f2;
            }

            .training-user-access-cell.is-software {
                background: #f7faff;
            }

            .training-user-access-cell.is-training {
                background: #f5fdfa;
            }

            .training-user-id-cell {
                min-width: 6rem;
                font-variant-numeric: tabular-nums;
                text-align: center !important;
            }

            .training-user-action-cell {
                min-width: 9rem;
                text-align: center !important;
            }

            .training-user-edit-button {
                display: inline-flex;
                min-height: 2rem;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                border: 1px solid #1d4ed8;
                border-radius: 6px;
                padding: 0.35rem 0.7rem;
                color: #1d4ed8;
                background: #ffffff;
                font-size: 0.72rem;
                font-weight: 800;
                transition: background-color 160ms ease, color 160ms ease;
            }

            .training-user-edit-button:hover {
                color: #ffffff;
                background: #1d4ed8;
            }

            .training-restricted-value {
                color: #9a6510;
                font-size: 0.72rem;
                font-weight: 700;
            }

            .training-access-state {
                display: inline-flex;
                min-height: 1.8rem;
                align-items: center;
                justify-content: center;
                border: 1px solid;
                border-radius: 6px;
                padding: 0.25rem 0.65rem;
                font-size: 0.7rem;
                font-weight: 800;
            }

            .training-access-state.is-active {
                border-color: #86efac;
                color: #047857;
                background: #ecfdf5;
            }

            .training-access-state.is-blocked {
                border-color: #fca5a5;
                color: #b91c1c;
                background: #fef2f2;
            }

            .training-table th,
            .training-table td {
                border-bottom: 1px solid #e7edf5;
                padding: 0.9rem 1rem;
                color: var(--training-ink);
                text-align: left;
                vertical-align: middle;
                white-space: nowrap;
            }

            .training-table th {
                color: #52687f;
                background: #fbfdff;
                font-size: 0.78rem;
                font-weight: 800;
            }

            .training-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .training-table td.training-table-empty {
                padding: 2.5rem 1rem;
                color: var(--training-soft);
                text-align: center;
            }

            .training-person {
                gap: 0.7rem;
            }

            .training-position-cell,
            .training-score-cell {
                white-space: normal !important;
            }

            .training-position-cell {
                min-width: 13rem;
            }

            .training-position-list {
                display: grid;
                gap: 0.35rem;
            }

            .training-position-list > span {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
                color: var(--training-soft);
                font-size: 0.78rem;
                line-height: 1.35;
            }

            .training-position-list > span.is-current {
                color: var(--training-ink);
                font-weight: 750;
            }

            .training-position-list small {
                border: 1px solid #a7e8d0;
                border-radius: 999px;
                padding: 0.1rem 0.42rem;
                color: #087f73;
                background: #effcf8;
                font-size: 0.62rem;
                font-weight: 800;
            }

            .training-hire-date {
                min-width: 8.5rem;
                font-variant-numeric: tabular-nums;
            }

            .training-score-cell {
                min-width: 21rem;
            }

            .training-score-heading {
                min-width: 21rem;
                line-height: 1.35;
                white-space: normal !important;
            }

            .training-score-summary {
                display: flex;
                align-items: baseline;
                gap: 0.55rem;
                margin-bottom: 0.45rem;
            }

            .training-score-summary strong {
                color: #087f73;
                font-size: 1rem;
            }

            .training-score-summary span {
                color: var(--training-soft);
                font-size: 0.72rem;
            }

            .training-score-list {
                display: grid;
                gap: 0.3rem;
            }

            .training-score-list > span {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                color: var(--training-soft);
                font-size: 0.72rem;
                line-height: 1.35;
            }

            .training-score-list > span > span {
                overflow: hidden;
                max-width: 15rem;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-score-list strong {
                flex: none;
                color: var(--training-ink);
            }

            .training-avatar {
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border-radius: 999px;
                font-size: 0.7rem;
                font-weight: 850;
            }

            .training-avatar.is-rose {
                color: #9f344b;
                background: #ffe1e8;
            }

            .training-avatar.is-violet {
                color: #6142bd;
                background: #efeaff;
            }

            .training-avatar.is-mint {
                color: #087f73;
                background: #dff8ef;
            }

            .training-avatar.is-cyan,
            .training-avatar.is-sky {
                color: #176b87;
                background: #ddf8fb;
            }

            .training-program-cell {
                min-width: 15rem;
            }

            .training-program-cell > * + * {
                margin-top: 0.45rem;
            }

            .training-start-date-cell {
                min-width: 8.5rem;
            }

            .training-start-date-list {
                display: grid;
                gap: 1rem;
                color: #36516d;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .training-start-date-list > span {
                display: flex;
                min-height: 1.45rem;
                align-items: center;
            }

            .training-completed-program {
                display: flex;
                align-items: flex-start;
                gap: 0.45rem;
                color: #087f73;
                font-weight: 700;
                line-height: 1.35;
            }

            .training-completed-program i {
                margin-top: 0.15rem;
            }

            .training-current-program {
                display: grid;
                gap: 0.4rem;
            }

            .training-current-program-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .training-current-program-heading span {
                font-weight: 700;
                line-height: 1.35;
            }

            .training-current-program-heading strong {
                flex: none;
                color: var(--training-teal-dark);
                font-size: 0.82rem;
            }

            .training-current-program-detail {
                color: var(--training-soft);
                font-size: 0.72rem;
                line-height: 1.35;
            }

            .training-current-program .training-progress-track {
                width: 100%;
                max-width: 12rem;
            }

            .training-empty-program {
                color: var(--training-soft);
            }

            .training-progress-track {
                display: block;
                overflow: hidden;
                width: 5.5rem;
                height: 0.35rem;
                border-radius: 999px;
                background: #dfe5ec;
            }

            .training-progress-track span {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #0b6ce8, var(--training-teal));
            }

            .training-employment-status {
                position: relative;
                display: inline-flex;
                align-items: center;
                min-width: 8.6rem;
                border: 1px solid transparent;
                border-radius: 8px;
                padding-left: 0.65rem;
            }

            .training-employment-status select {
                width: 100%;
                min-height: 2.15rem;
                appearance: none;
                border: 0;
                padding: 0 1.8rem 0 0.5rem;
                color: inherit;
                background: transparent;
                font-size: 0.75rem;
                font-weight: 800;
                cursor: pointer;
            }

            .training-employment-status select:focus {
                outline: 2px solid rgba(29, 112, 216, 0.35);
                outline-offset: 1px;
            }

            .training-employment-status > i {
                position: absolute;
                right: 0.65rem;
                pointer-events: none;
                font-size: 0.65rem;
            }

            .training-employment-dot {
                width: 0.45rem;
                height: 0.45rem;
                flex: 0 0 auto;
                border-radius: 50%;
                background: currentColor;
            }

            .training-employment-status.is-hired {
                border-color: #a7e8d0;
                color: #087f73;
                background: #effcf8;
            }

            .training-employment-status.is-inactive {
                border-color: #ffc6c6;
                color: #b42318;
                background: #fff1f1;
            }

            .training-icon-button {
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border: 0;
                color: #52687f;
                background: transparent;
            }

            .training-icon-button:hover {
                background: #f2f6fb;
            }

            .training-add-training-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                min-width: 6.5rem;
                min-height: 2.1rem;
                border: 1px solid var(--training-teal);
                border-radius: 8px;
                padding: 0 0.75rem;
                color: var(--training-teal-dark);
                background: #ffffff;
                font-weight: 750;
            }

            .training-add-training-button:hover {
                color: #ffffff;
                background: var(--training-teal-dark);
            }

            .training-pagination {
                justify-content: flex-end;
                gap: 0.55rem;
                margin-top: 0.9rem;
                color: var(--training-ink);
                font-weight: 750;
            }

            .training-pagination a,
            .training-pagination button {
                display: grid;
                width: 2.35rem;
                height: 2.35rem;
                place-items: center;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                color: #52687f;
                background: #ffffff;
            }

            .training-pagination button:disabled {
                cursor: not-allowed;
                color: #b8c3cf;
                background: #f8fafc;
            }

            .training-obligation-flow {
                display: grid;
                grid-template-columns: minmax(8rem, 1fr) auto minmax(8rem, 1fr) auto minmax(8rem, 1fr) auto minmax(9rem, 1fr);
                align-items: center;
                gap: 0.7rem;
                margin-top: 1rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.85rem 1rem;
                background: #ffffff;
            }

            .training-obligation-stage {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                align-items: center;
                column-gap: 0.65rem;
                color: #60758a;
            }

            .training-obligation-stage > span {
                display: grid;
                grid-row: 1 / span 2;
                width: 2.25rem;
                height: 2.25rem;
                place-items: center;
                border-radius: 999px;
                color: #60758a;
                background: #e9eef4;
            }

            .training-obligation-stage strong,
            .training-obligation-stage small {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-obligation-stage strong {
                font-size: 0.82rem;
            }

            .training-obligation-stage small {
                color: #7b8fa2;
                font-size: 0.7rem;
                font-weight: 650;
            }

            .training-obligation-stage.is-complete > span {
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-obligation-stage.is-complete strong {
                color: var(--training-teal-dark);
            }

            .training-obligation-stage.is-active {
                color: #1958bb;
            }

            .training-obligation-stage.is-active > span {
                color: #ffffff;
                background: var(--training-blue);
                box-shadow: 0 0 0 0.3rem #e8f1ff;
            }

            .training-obligation-stage.is-active small {
                color: #315b94;
            }

            .training-obligation-arrow {
                color: #9aabba;
                font-size: 0.72rem;
            }

            .training-learner-access {
                display: flex;
                align-items: flex-end;
                justify-content: flex-start;
                gap: 0.75rem;
                margin-bottom: 0.85rem;
            }

            .training-learner-access label {
                display: grid;
                width: min(100%, 32rem);
                gap: 0.4rem;
                color: var(--training-soft);
                font-size: 0.78rem;
                font-weight: 750;
            }

            .training-learner-select-control {
                position: relative;
                display: block;
            }

            .training-learner-select-control select {
                width: 100%;
                min-height: 2.65rem;
                appearance: none;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.65rem 2.5rem 0.65rem 0.8rem;
                color: var(--training-ink);
                background: #ffffff;
                font: inherit;
                font-size: 0.88rem;
                font-weight: 600;
            }

            .training-learner-select-control select:focus {
                border-color: var(--training-blue);
                outline: 3px solid rgba(29, 112, 216, 0.14);
            }

            .training-learner-select-control i {
                position: absolute;
                top: 50%;
                right: 0.85rem;
                color: var(--training-soft);
                pointer-events: none;
                transform: translateY(-50%);
            }

            .training-learner-access > .training-primary-button {
                flex: 0 0 auto;
                min-width: 8.75rem;
                min-height: 2.65rem;
            }

            .training-learner-hero {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 1.1rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1.25rem;
                background: linear-gradient(135deg, #ffffff, #f0fbf9);
            }

            .training-learner-hero h2 {
                margin: 0.15rem 0 0;
                font-size: 1.65rem;
                font-weight: 800;
            }

            .training-learner-hero > .training-primary-button {
                flex: 0 0 auto;
                min-width: 12.5rem;
                min-height: 2.75rem;
                padding-inline: 1.25rem;
                color: #ffffff;
            }

            .training-learner-hero > .training-primary-button span,
            .training-learner-hero > .training-primary-button i {
                color: inherit;
                font-size: 0.9rem;
                line-height: 1;
            }

            .training-learner-metrics {
                display: grid;
                grid-template-columns: minmax(12rem, 1.4fr) repeat(3, minmax(9rem, 1fr));
                gap: 0.9rem;
                margin-top: 0.9rem;
            }

            .training-learner-metrics article {
                display: grid;
                gap: 0.45rem;
                min-height: 6rem;
                align-content: center;
                padding: 1rem;
            }

            .training-learner-metrics strong {
                color: var(--training-teal-dark);
                font-size: 1.75rem;
                line-height: 1;
            }

            .training-learner-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.35fr) minmax(19rem, 0.75fr);
                gap: 1rem;
                align-items: start;
                margin-top: 0.9rem;
            }

            .training-learner-grid.is-outline-only {
                grid-template-columns: 1fr;
            }

            .training-video-panel {
                overflow: hidden;
            }

            .training-video-heading {
                justify-content: space-between;
                gap: 1rem;
                padding: 1.15rem;
            }

            .training-video-heading h3 {
                margin: 0.2rem 0 0;
                font-size: 1.45rem;
                font-weight: 800;
            }

            .training-video-heading b,
            .training-route-heading strong {
                border-radius: 999px;
                padding: 0.45rem 0.65rem;
                color: var(--training-teal-dark);
                background: var(--training-mint);
                font-size: 0.78rem;
            }

            .training-video-frame {
                display: grid;
                min-height: 18rem;
                place-items: center;
                gap: 0.8rem;
                margin: 0 1.15rem;
                border-radius: 8px;
                color: #ffffff;
                background: linear-gradient(135deg, rgba(47, 63, 130, 0.96), rgba(15, 169, 143, 0.8));
                text-align: center;
            }

            .training-video-frame i {
                display: grid;
                width: 4rem;
                height: 4rem;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, 0.45);
                border-radius: 999px;
                font-size: 1.4rem;
            }

            .training-video-frame > strong {
                font-size: 1.05rem;
            }

            .training-video-frame > [data-video-playback-status] {
                color: rgba(255, 255, 255, 0.85);
                font-size: 0.8rem;
                font-weight: 700;
            }

            .training-video-playback-track {
                display: block;
                width: min(24rem, 70%);
                height: 0.38rem;
                overflow: hidden;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.28);
            }

            .training-video-playback-track > span {
                display: block;
                width: 0;
                height: 100%;
                border-radius: inherit;
                background: #ffffff;
                transition: width 180ms linear;
            }

            .training-chapter-detail {
                justify-content: space-between;
                gap: 1rem;
                padding: 1.15rem;
            }

            .training-chapter-detail h4 {
                margin: 0.25rem 0;
                font-size: 1.15rem;
                font-weight: 800;
            }

            .training-chapter-detail p {
                margin: 0;
                color: var(--training-soft);
            }

            .training-route-panel {
                display: grid;
                gap: 0.8rem;
                padding: 1rem;
            }

            .training-content-outline {
                display: grid;
                gap: 0.75rem;
            }

            .training-content-chapter {
                overflow: hidden;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                background: #ffffff;
            }

            .training-content-chapter > header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.65rem 0.75rem;
                color: #31516f;
                background: #f8fafc;
            }

            .training-content-chapter > header strong {
                font-size: 0.8rem;
            }

            .training-content-chapter > header small {
                color: var(--training-soft);
                font-size: 0.7rem;
                font-weight: 700;
            }

            .training-content-video {
                display: grid;
                width: 100%;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.65rem;
                min-height: 3.2rem;
                border: 0;
                border-top: 1px solid var(--training-line);
                padding: 0.55rem 0.7rem;
                color: var(--training-ink);
                background: #ffffff;
                text-align: left;
            }

            .training-content-video > span:first-child {
                display: grid;
                width: 1.7rem;
                height: 1.7rem;
                place-items: center;
                border-radius: 999px;
                color: #60758a;
                background: #e9eef4;
                font-size: 0.68rem;
            }

            .training-content-video strong,
            .training-content-video small {
                display: block;
            }

            .training-content-video strong {
                overflow: hidden;
                font-size: 0.76rem;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .training-content-video small {
                color: var(--training-soft);
                font-size: 0.68rem;
                font-weight: 650;
            }

            .training-content-video > em {
                color: #60758a;
                font-size: 0.66rem;
                font-style: normal;
                font-weight: 800;
                white-space: nowrap;
            }

            .training-content-video.is-complete > span:first-child {
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-content-video.is-current {
                color: #1958bb;
                background: #f4f8ff;
                box-shadow: inset 3px 0 0 var(--training-blue);
                cursor: pointer;
            }

            .training-content-video.is-current > span:first-child {
                color: #ffffff;
                background: var(--training-blue);
            }

            .training-content-video:disabled {
                cursor: not-allowed;
            }

            .training-route-heading {
                justify-content: space-between;
                gap: 0.7rem;
            }

            .training-route-step {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.75rem;
                min-height: 4rem;
                border: 1px solid var(--training-line);
                padding: 0.75rem;
                color: var(--training-ink);
                background: #ffffff;
                text-align: left;
            }

            .training-route-step > span {
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border-radius: 999px;
                color: #52687f;
                background: #edf2f7;
                font-weight: 850;
            }

            .training-route-step strong,
            .training-route-step small {
                display: block;
            }

            .training-route-step small {
                color: var(--training-soft);
                font-weight: 650;
            }

            .training-route-step em {
                color: var(--training-soft);
                font-size: 0.75rem;
                font-style: normal;
                font-weight: 800;
            }

            .training-route-step.is-done {
                border-color: rgba(15, 169, 143, 0.45);
                background: #f2fffb;
            }

            .training-route-step.is-done > span,
            .training-route-step.is-current > span {
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-route-step.is-current {
                border-color: rgba(29, 112, 216, 0.45);
                background: #f4f8ff;
            }

            .training-route-step:disabled {
                cursor: not-allowed;
            }

            .training-learner-view[hidden],
            .training-learner-dialog [hidden] {
                display: none;
            }

            .training-learner-tabs button {
                cursor: pointer;
            }

            .training-learner-section,
            .training-results-section {
                min-height: 32rem;
                margin-top: 1rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1.35rem;
                background: #ffffff;
            }

            .training-learner-section-heading {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .training-learner-section-heading h2 {
                margin: 0;
                color: #0f172a;
                font-size: 1.55rem;
                font-weight: 800;
            }

            .training-learner-section-heading p {
                margin: 0.35rem 0 0;
                color: var(--training-soft);
                font-size: 0.92rem;
            }

            .training-learning-path {
                position: relative;
                display: grid;
                grid-template-columns: repeat(4, minmax(8rem, 1fr));
                gap: 0;
                margin-top: 1.2rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1rem 2rem;
                background: #ffffff;
            }

            .training-learning-path-step {
                position: relative;
                display: grid;
                justify-items: center;
                gap: 0.45rem;
                color: #52687f;
                text-align: center;
            }

            .training-learning-path-step:not(:last-child)::after {
                position: absolute;
                z-index: 0;
                top: 1.2rem;
                left: calc(50% + 1.55rem);
                width: calc(100% - 3.1rem);
                height: 3px;
                border-radius: 999px;
                background: #e5ebf1;
                content: '';
            }

            .training-learning-path-step.is-approved:not(:last-child)::after {
                background: var(--training-teal);
            }

            .training-learning-path-step > span {
                position: relative;
                z-index: 1;
                display: grid;
                width: 2.45rem;
                height: 2.45rem;
                place-items: center;
                border-radius: 999px;
                color: #52687f;
                background: #e8edf3;
                font-size: 1rem;
                font-weight: 850;
            }

            .training-learning-path-step > strong {
                font-size: 0.84rem;
            }

            .training-learning-path-step.is-approved,
            .training-learning-path-step.is-approved > span {
                color: var(--training-teal-dark);
            }

            .training-learning-path-step.is-approved > span {
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-learning-path-step.is-current,
            .training-learning-path-step.is-current > span {
                color: var(--training-blue);
            }

            .training-learning-path-step.is-current > span {
                color: #ffffff;
                background: var(--training-blue);
            }

            .training-module-route {
                display: grid;
                gap: 0.8rem;
                margin-top: 1rem;
            }

            .training-learner-module {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 1rem;
                min-height: 6.5rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1rem 1.25rem;
                background: #ffffff;
            }

            .training-learner-module.is-approved {
                border-color: rgba(15, 169, 143, 0.4);
                background: #f3fffb;
            }

            .training-learner-module.is-current {
                border-color: rgba(29, 112, 216, 0.45);
                background: #f7faff;
            }

            .training-learner-module-marker {
                display: grid;
                width: 3.75rem;
                height: 3.75rem;
                place-items: center;
                border-radius: 999px;
                color: #60758a;
                background: #edf2f7;
                font-size: 1.45rem;
                font-weight: 850;
            }

            .training-learner-module.is-approved .training-learner-module-marker {
                border: 0.65rem solid #d9f8ef;
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-learner-module.is-approved .training-learner-module-marker i {
                display: none;
            }

            .training-learner-module.is-approved .training-learner-module-marker::after {
                content: '\2713';
                font-size: 1.15rem;
                line-height: 1;
            }

            .training-learner-module.is-current .training-learner-module-marker {
                color: var(--training-blue);
                background: #e2edff;
            }

            .training-learner-module-content {
                min-width: 0;
                border-left: 1px solid var(--training-line);
                padding-left: 1rem;
            }

            .training-learner-module-content h3 {
                margin: 0;
                color: #102a43;
                font-size: 1.1rem;
                font-weight: 800;
            }

            .training-learner-module-content p {
                margin: 0.45rem 0 0;
                color: var(--training-soft);
                font-size: 0.86rem;
            }

            .training-module-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                margin-top: 0.65rem;
                color: var(--training-soft);
                font-size: 0.85rem;
            }

            .training-module-meta span,
            .training-module-meta strong {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .training-module-meta strong {
                color: var(--training-teal-dark);
            }

            .training-module-progress {
                display: grid;
                grid-template-columns: auto minmax(8rem, 18rem);
                align-items: center;
                gap: 0.75rem;
                margin-top: 0.65rem;
            }

            .training-module-progress > strong {
                color: var(--training-blue);
                font-size: 1.45rem;
            }

            .training-learner-module-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .training-state-pill {
                display: inline-flex;
                width: max-content;
                min-height: 2rem;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                border-radius: 999px;
                padding: 0.3rem 0.75rem;
                font-size: 0.78rem;
                font-weight: 800;
                white-space: nowrap;
            }

            .training-state-pill.is-approved {
                color: var(--training-teal-dark);
                background: #dcf8ef;
            }

            .training-state-pill.is-current {
                color: #1958bb;
                background: #eaf2ff;
            }

            .training-state-pill.is-pending {
                border: 1px solid #f5d56c;
                color: #9b6500;
                background: #fff9dc;
            }

            .training-state-pill.is-review {
                color: #075985;
                background: #e0f2fe;
            }

            .training-state-pill.is-corrections {
                color: #b45309;
                background: #fff0dc;
            }

            .training-state-pill.is-locked {
                color: #60758a;
                background: #edf1f5;
            }

            .training-information-note {
                display: flex;
                align-items: flex-start;
                gap: 0.8rem;
                margin-top: 1rem;
                border: 1px solid #a9cdfd;
                border-radius: 8px;
                padding: 0.9rem 1rem;
                color: #1958bb;
                background: #f4f8ff;
            }

            .training-information-note > i {
                margin-top: 0.1rem;
                font-size: 1.2rem;
            }

            .training-information-note p {
                margin: 0;
                color: #315b94;
                font-size: 0.85rem;
            }

            .training-active-task-summary {
                margin-top: 1rem;
            }

            .training-active-task-summary > span {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                border-radius: 999px;
                padding: 0.45rem 0.7rem;
                color: var(--training-teal-dark);
                background: var(--training-mint);
                font-size: 0.8rem;
            }

            .training-active-task-summary i {
                width: 0.55rem;
                height: 0.55rem;
                border-radius: 999px;
                background: var(--training-teal);
            }

            .training-task-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 0.65rem;
                margin-top: 1.2rem;
            }

            .training-task-filters button {
                min-height: 2.45rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.4rem 1rem;
                color: #31516f;
                background: #ffffff;
                font-size: 0.82rem;
                font-weight: 750;
            }

            .training-task-filters button.is-active {
                border-color: #5ea0f4;
                color: #1958bb;
                background: #f7faff;
                box-shadow: inset 0 -2px 0 var(--training-blue);
            }

            .training-task-table-wrap {
                overflow-x: auto;
                margin-top: 0.9rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
            }

            .training-task-table {
                width: 100%;
                min-width: 58rem;
                border-collapse: separate;
                border-spacing: 0;
                color: var(--training-ink);
                font-size: 0.86rem;
            }

            .training-task-table th {
                height: 3rem;
                padding: 0.75rem 1rem;
                color: var(--training-soft);
                background: #fbfcfd;
                text-align: left;
                font-size: 0.78rem;
                font-weight: 800;
            }

            .training-task-table td {
                height: 4.6rem;
                border-top: 1px solid var(--training-line);
                padding: 0.7rem 1rem;
                background: #ffffff;
                vertical-align: middle;
            }

            .training-task-table tr.is-pending td {
                background: #f7faff;
            }

            .training-task-table tr.is-locked td {
                color: #52687f;
            }

            .training-task-table td:nth-child(1) {
                width: 38%;
            }

            .training-task-table td:nth-child(2) {
                width: 27%;
            }

            .training-task-table td:nth-child(3) {
                width: 20%;
            }

            .training-task-table td:last-child {
                width: 15%;
            }

            .training-task-table td > span,
            .training-task-table td > strong {
                display: block;
            }

            .training-task-table td > span {
                color: var(--training-soft);
            }

            .training-task-table td > strong {
                margin-top: 0.2rem;
            }

            .training-task-name {
                display: flex;
                align-items: center;
                gap: 0.85rem;
            }

            .training-task-name > span {
                display: grid;
                width: 2rem;
                height: 2rem;
                flex: 0 0 2rem;
                place-items: center;
                border-radius: 999px;
                color: #52687f;
                background: #edf2f7;
                font-weight: 850;
            }

            tr.is-approved .training-task-name > span {
                color: #ffffff;
                background: var(--training-teal);
            }

            tr.is-pending .training-task-name > span,
            tr.is-review .training-task-name > span {
                color: #ffffff;
                background: var(--training-blue);
            }

            [data-task-empty-row] td {
                height: 8rem;
                color: var(--training-soft);
                text-align: center;
            }

            .training-result-hero {
                display: grid;
                grid-template-columns: auto minmax(16rem, 1fr) auto;
                align-items: center;
                gap: 1.5rem;
                margin-top: 1rem;
                border: 1px solid rgba(15, 169, 143, 0.45);
                border-radius: 8px;
                padding: 1.2rem 1.5rem;
                background: #f5fffc;
            }

            .training-result-hero.is-failed {
                border-color: rgba(220, 90, 90, 0.45);
                background: #fff8f8;
            }

            .training-score-ring {
                position: relative;
                display: grid;
                width: 8.5rem;
                height: 8.5rem;
                flex: 0 0 8.5rem;
                place-items: center;
                border-radius: 999px;
                background: conic-gradient(var(--training-teal) calc(var(--score) * 1%), #e7edf2 0);
            }

            .training-score-ring::before {
                position: absolute;
                width: 6.7rem;
                height: 6.7rem;
                border-radius: inherit;
                background: #ffffff;
                content: '';
            }

            .training-score-ring strong {
                position: relative;
                color: var(--training-teal-dark);
                font-size: 2.5rem;
            }

            .training-result-hero.is-failed .training-score-ring {
                background: conic-gradient(#dc5a5a calc(var(--score) * 1%), #f1e7e7 0);
            }

            .training-result-hero.is-failed .training-score-ring strong {
                color: #b42323;
            }

            .training-result-summary h3 {
                margin: 0.75rem 0 0.5rem;
                font-size: 1.45rem;
                font-weight: 800;
            }

            .training-result-summary p {
                margin: 0.2rem 0;
                color: var(--training-soft);
                font-size: 0.9rem;
            }

            .training-result-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 0.75rem;
            }

            .training-result-actions button {
                min-width: 10.5rem;
            }

            .training-result-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
                margin-top: 1rem;
            }

            .training-result-card {
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1.1rem;
                background: #ffffff;
            }

            .training-result-card > h3 {
                margin: 0 0 1rem;
                font-size: 1rem;
                font-weight: 800;
            }

            .training-score-breakdown {
                display: grid;
                gap: 1.1rem;
            }

            .training-score-breakdown > div {
                display: grid;
                grid-template-columns: 6.5rem minmax(6rem, 1fr) 3rem;
                align-items: center;
                gap: 0.75rem;
                color: #31516f;
                font-size: 0.85rem;
            }

            .training-score-breakdown > div > strong {
                text-align: right;
            }

            .training-result-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.75rem;
                margin-top: 1.4rem;
                border-top: 1px solid var(--training-line);
                padding-top: 1rem;
            }

            .training-result-metrics > div {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .training-result-metrics i {
                display: grid;
                width: 2.8rem;
                height: 2.8rem;
                flex: 0 0 2.8rem;
                place-items: center;
                border-radius: 8px;
                color: var(--training-teal-dark);
                background: var(--training-mint);
                font-size: 1.2rem;
            }

            .training-result-metrics span,
            .training-result-metrics strong {
                display: block;
            }

            .training-result-metrics span {
                color: var(--training-soft);
                font-size: 0.8rem;
            }

            .training-result-metrics strong {
                margin-top: 0.2rem;
                color: var(--training-ink);
                font-size: 1.25rem;
            }

            .training-result-history {
                overflow: hidden;
                border: 1px solid var(--training-line);
                border-radius: 8px;
            }

            .training-result-history > div {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto auto;
                align-items: center;
                gap: 0.75rem;
                min-height: 3.8rem;
                padding: 0.65rem 0.8rem;
            }

            .training-result-history > div + div {
                border-top: 1px solid var(--training-line);
            }

            .training-result-history > div > span,
            .training-result-history > div > i {
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border-radius: 999px;
                color: #52687f;
                background: #e9eef4;
                font-weight: 850;
            }

            .training-result-history .is-approved > span,
            .training-result-history .is-approved > i {
                color: #ffffff;
                background: var(--training-teal);
            }

            .training-result-history .is-current > span {
                color: #ffffff;
                background: var(--training-blue);
            }

            .training-result-history .is-failed > span,
            .training-result-history .is-failed > i {
                color: #ffffff;
                background: #dc5a5a;
            }

            .training-result-history p,
            .training-result-history b,
            .training-result-history small {
                display: block;
                margin: 0;
            }

            .training-result-history b {
                color: #31516f;
                text-align: right;
            }

            .training-result-history small {
                margin-top: 0.1rem;
                color: var(--training-soft);
                font-size: 0.76rem;
                font-weight: 650;
            }

            .training-result-history .is-current b {
                border-radius: 999px;
                padding: 0.35rem 0.7rem;
                color: #1958bb;
                background: #eaf2ff;
                font-size: 0.76rem;
            }

            .training-result-history .is-failed b {
                color: #b42323;
            }

            .training-result-history .is-locked b {
                color: #60758a;
                font-size: 0.76rem;
            }

            .training-certificate-card {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 1.25rem;
                margin-top: 1.2rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 1.25rem;
                background: #fbfcfe;
            }

            .training-certificate-card.is-available {
                border-color: rgba(15, 169, 143, 0.45);
                background: #f3fffb;
            }

            .training-certificate-icon {
                display: grid;
                width: 4rem;
                height: 4rem;
                place-items: center;
                border-radius: 8px;
                color: #9b6500;
                background: #fff5c7;
                font-size: 1.6rem;
            }

            .training-certificate-content > span {
                color: var(--training-soft);
                font-size: 0.78rem;
                font-weight: 750;
            }

            .training-certificate-content h3 {
                margin: 0.2rem 0 0.35rem;
                font-size: 1.1rem;
                font-weight: 800;
            }

            .training-certificate-content p {
                margin: 0;
                color: var(--training-soft);
                font-size: 0.86rem;
            }

            .training-certificate-progress {
                display: grid;
                grid-template-columns: minmax(8rem, 18rem) auto;
                align-items: center;
                gap: 0.7rem;
                margin-top: 0.8rem;
            }

            .training-certificate-progress > strong {
                color: var(--training-teal-dark);
                font-size: 0.8rem;
            }

            .training-certificate-actions {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 0.75rem;
            }

            .training-video-frame {
                width: calc(100% - 2.3rem);
                border: 0;
                cursor: pointer;
            }

            .training-video-frame.is-playing i {
                animation: training-video-pulse 1.25s ease-in-out infinite alternate;
            }

            .training-video-frame:disabled {
                cursor: default;
                opacity: 1;
            }

            @keyframes training-video-pulse {
                from { transform: scale(0.95); opacity: 0.72; }
                to { transform: scale(1.05); opacity: 1; }
            }

            .training-learner-dialog {
                width: min(42rem, calc(100vw - 2rem));
                max-width: none;
                max-height: calc(100vh - 2rem);
                overflow: auto;
                border: 0;
                border-radius: 8px;
                padding: 0;
                color: var(--training-ink);
                background: transparent;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            }

            .training-video-dialog {
                width: min(68rem, calc(100vw - 2rem));
            }

            .training-video-dialog .training-video-panel {
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .training-video-dialog .training-video-frame {
                min-height: min(32rem, calc(100vh - 18rem));
                margin-top: 1.15rem;
            }

            .training-video-dialog .training-learner-dialog-shell > header b {
                color: var(--training-teal-dark);
            }

            .training-video-dialog [data-close-learner-video] {
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 2.25rem;
                border: 1px solid var(--training-line);
                color: #334e68;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(16, 42, 67, 0.08);
            }

            .training-video-dialog [data-close-learner-video]:hover {
                border-color: #b8c6d3;
                color: #102a43;
                background: #f2f6fb;
            }

            .training-video-dialog [data-close-learner-video]:focus-visible {
                outline: 3px solid rgba(29, 112, 216, 0.2);
                outline-offset: 2px;
            }

            .training-video-dialog [data-close-learner-video] .training-close-glyph {
                color: inherit;
                font-size: 1.55rem;
                font-weight: 500;
                line-height: 1;
                text-transform: none;
            }

            .training-learner-dialog::backdrop {
                background: rgba(15, 23, 42, 0.58);
            }

            .training-learner-dialog-shell {
                overflow: hidden;
                border-radius: 8px;
                background: #ffffff;
            }

            .training-learner-dialog-shell > header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                border-bottom: 1px solid var(--training-line);
                padding: 1rem 1.15rem;
            }

            .training-learner-dialog-shell > header span {
                color: var(--training-blue);
                font-size: 0.72rem;
                font-weight: 850;
                text-transform: uppercase;
            }

            .training-learner-dialog-shell > header h3 {
                margin: 0.2rem 0 0;
                font-size: 1.25rem;
                font-weight: 800;
            }

            .training-learner-dialog-shell > header p {
                margin: 0.2rem 0 0;
                color: var(--training-soft);
                font-size: 0.82rem;
            }

            .training-learner-dialog form {
                display: grid;
                gap: 1rem;
                padding: 1.15rem;
            }

            .training-learner-dialog form label {
                display: grid;
                gap: 0.45rem;
                color: #31516f;
                font-size: 0.82rem;
                font-weight: 750;
            }

            .training-learner-dialog form label b {
                color: #dc2626;
            }

            .training-learner-dialog textarea,
            .training-learner-dialog input[type='file'] {
                width: 100%;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.75rem;
                color: var(--training-ink);
                background: #ffffff;
                font: inherit;
            }

            .training-learner-dialog textarea:focus,
            .training-learner-dialog input[type='file']:focus {
                border-color: #5ea0f4;
                outline: 2px solid rgba(29, 112, 216, 0.12);
            }

            .training-task-file-field small {
                color: var(--training-soft);
                font-weight: 650;
            }

            .training-task-instructions {
                display: flex;
                gap: 0.75rem;
                border: 1px solid #bfd8fa;
                border-radius: 8px;
                padding: 0.85rem;
                color: #315b94;
                background: #f5f9ff;
            }

            .training-exam-dialog {
                width: min(50rem, calc(100vw - 2rem));
            }

            .training-exam-intro {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                border: 1px solid #bfd8fa;
                border-radius: 8px;
                padding: 0.85rem;
                color: #315b94;
                background: #f5f9ff;
            }

            .training-exam-intro > i {
                margin-top: 0.1rem;
                color: var(--training-blue);
            }

            .training-exam-intro p {
                margin: 0;
                font-size: 0.84rem;
            }

            .training-learner-exam-questions {
                display: grid;
                gap: 0.85rem;
            }

            .training-exam-question {
                margin: 0;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.9rem;
            }

            .training-exam-question legend {
                width: 100%;
                padding: 0 0 0.7rem;
                color: var(--training-ink);
                font-size: 0.9rem;
                font-weight: 800;
            }

            .training-exam-question > label {
                display: flex !important;
                grid-template-columns: none;
                align-items: flex-start;
                gap: 0.65rem !important;
                min-height: 2.45rem;
                border: 1px solid transparent;
                border-radius: 8px;
                padding: 0.55rem 0.65rem;
                cursor: pointer;
            }

            .training-exam-question > label:hover {
                border-color: #bfd8fa;
                background: #f7faff;
            }

            .training-exam-question input {
                width: 1rem;
                height: 1rem;
                flex: 0 0 1rem;
                margin-top: 0.12rem;
                accent-color: var(--training-blue);
            }

            .training-exam-validation {
                margin: 0;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 0.7rem 0.8rem;
                color: #b42323;
                background: #fff7f7;
                font-size: 0.82rem;
                font-weight: 750;
            }

            .training-task-instructions p {
                margin: 0;
                font-size: 0.84rem;
            }

            .training-learner-dialog footer {
                display: flex;
                justify-content: flex-end;
                gap: 0.65rem;
            }

            .training-task-submission-view {
                display: grid;
                gap: 0.8rem;
                padding: 1.15rem;
            }

            .training-task-submission-view > div {
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.85rem;
                background: #fbfcfd;
            }

            .training-task-submission-view span {
                color: var(--training-soft);
                font-size: 0.76rem;
                font-weight: 800;
            }

            .training-task-submission-view p {
                margin: 0.35rem 0 0;
                white-space: pre-wrap;
            }

            .training-answer-review-list {
                display: grid;
                gap: 0.65rem;
                padding: 1.15rem;
            }

            .training-answer-review-list article {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.75rem;
                border: 1px solid var(--training-line);
                border-radius: 8px;
                padding: 0.85rem;
            }

            .training-answer-review-list article > span,
            .training-answer-review-list article > i {
                display: grid;
                width: 2rem;
                height: 2rem;
                place-items: center;
                border-radius: 999px;
                color: #ffffff;
            }

            .training-answer-review-list .is-correct > span,
            .training-answer-review-list .is-correct > i {
                background: var(--training-teal);
            }

            .training-answer-review-list .is-incorrect > span,
            .training-answer-review-list .is-incorrect > i {
                background: #dc5a5a;
            }

            .training-answer-review-list p {
                margin: 0.25rem 0 0;
                color: var(--training-soft);
                font-size: 0.82rem;
            }

            .training-learner-dialog-shell > footer {
                border-top: 1px solid var(--training-line);
                padding: 1rem 1.15rem;
            }

            .training-personnel-table table {
                min-width: 188rem;
                font-size: 0.76rem;
            }

            .training-personnel-table th,
            .training-personnel-table td,
            .training-personnel-table button,
            .training-personnel-table input,
            .training-personnel-table select {
                font-size: 0.76rem;
            }

            .training-personnel-table th,
            .training-personnel-table td {
                padding: 0.58rem 0.62rem;
                line-height: 1.28;
            }

            .training-personnel-table th {
                letter-spacing: 0;
            }

            .training-personnel-table th:first-child,
            .training-personnel-table td:first-child {
                min-width: 11rem;
                max-width: 13rem;
            }

            .training-personnel-table .training-person {
                min-width: 0;
                gap: 0.5rem;
            }

            .training-personnel-table .training-person strong {
                white-space: normal;
                font-size: 0.76rem;
                line-height: 1.28;
            }

            .training-personnel-table .training-avatar {
                width: 1.8rem;
                height: 1.8rem;
                flex: 0 0 1.8rem;
                font-size: 0.62rem;
            }

            .training-personnel-table .training-position-cell {
                min-width: 9.5rem;
            }

            .training-personnel-table .training-position-list > span,
            .training-personnel-table .training-current-program-detail,
            .training-personnel-table .training-score-summary span,
            .training-personnel-table .training-score-list > span,
            .training-personnel-table .training-restricted-value {
                font-size: 0.76rem;
            }

            .training-personnel-table .training-hire-date {
                min-width: 6.75rem;
            }

            .training-personnel-table .training-program-cell {
                min-width: 10.5rem;
                white-space: normal;
            }

            .training-personnel-table .training-score-cell {
                min-width: 10rem;
            }

            .training-personnel-table .training-score-heading {
                width: 10rem;
                min-width: 10rem;
                max-width: 10rem;
                line-height: 1.2;
                text-align: left;
            }

            .training-personnel-table .training-score-heading span {
                display: block;
            }

            .training-personnel-table .training-score-list > span > span {
                max-width: 7.5rem;
            }

            .training-personnel-table .training-user-subheading th,
            .training-personnel-table .training-user-access-cell {
                min-width: 11rem;
            }

            .training-personnel-table .training-user-id-cell {
                min-width: 4rem;
            }

            .training-personnel-table .training-user-action-cell {
                min-width: 5.5rem;
            }

            .training-personnel-table .training-employment-status {
                min-width: 7.25rem;
            }

            .training-personnel-table .training-add-training-button {
                min-width: 5.5rem;
                min-height: 1.9rem;
            }

            .training-personnel-table .training-user-edit-button {
                min-height: 1.9rem;
                padding: 0.25rem 0.5rem;
            }

            @media (max-width: 1180px) {
                .training-learner-metrics {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .training-project-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .training-module-workspace {
                    grid-template-columns: 1fr;
                }

                .training-chapter-fields {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .training-exam-general-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .training-new-question-toolbar {
                    grid-template-columns: auto auto 1fr minmax(11rem, 0.7fr) minmax(7rem, 0.4fr);
                }

                .training-question-toolbar-actions {
                    grid-column: 1 / -1;
                }

                .training-learner-grid {
                    grid-template-columns: 1fr;
                }

                .training-result-hero {
                    grid-template-columns: auto minmax(0, 1fr);
                }

                .training-result-actions {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 900px) {
                .training-obligation-flow {
                    grid-template-columns: repeat(7, max-content);
                    overflow-x: auto;
                    padding-bottom: 1rem;
                }

                .training-obligation-stage {
                    min-width: 9rem;
                }

                .training-page-header,
                .training-section-heading,
                .training-learner-hero,
                .training-chapter-detail {
                    align-items: stretch;
                    flex-direction: column;
                }

                .training-role-switch,
                .training-tabs,
                .training-filters,
                .training-learner-metrics,
                .training-project-grid,
                .training-module-fields,
                .training-chapter-fields {
                    grid-template-columns: 1fr;
                }

                .training-module-fields .is-wide,
                .training-upload-control {
                    grid-column: auto;
                }

                .training-tabs.is-programs {
                    grid-template-columns: 1fr;
                }

                .training-program-carousel {
                    gap: 0.45rem;
                }

                .training-program-carousel-arrow {
                    width: 2.15rem;
                    height: 2.15rem;
                    flex-basis: 2.15rem;
                }

                .training-program-new-tile {
                    flex-basis: 9.75rem;
                }

                .training-program-carousel-card {
                    flex-basis: min(16rem, calc(100vw - 8.5rem));
                }

                .training-card-actions {
                    grid-template-columns: 1fr;
                }

                .training-program-modal-body {
                    grid-template-columns: 1fr;
                }

                .training-assignment-module-list {
                    grid-template-columns: 1fr;
                    padding-left: 1rem;
                }

                .training-exam-meta-fields {
                    grid-template-columns: 1fr;
                }

                .training-exam-questions-heading,
                .training-exam-preview-heading,
                .training-exam-builder-header,
                .training-exam-builder-question-heading {
                    align-items: stretch;
                    flex-direction: column;
                }

                .training-exam-general-grid {
                    grid-template-columns: 1fr;
                }

                .training-exam-general-grid .is-wide {
                    grid-column: auto;
                }

                .training-new-question-toolbar {
                    grid-template-columns: auto auto minmax(0, 1fr);
                }

                .training-new-question-type,
                .training-new-question-points,
                .training-question-toolbar-actions {
                    grid-column: 1 / -1;
                }

                .training-question-toolbar-actions {
                    justify-content: flex-start;
                }

                .training-exam-builder-actions {
                    position: static;
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .training-program-modal-body .is-wide {
                    grid-column: auto;
                }

                .training-program-modal footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .training-assignment-actions {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .training-learner-tabs {
                    grid-template-columns: repeat(5, minmax(8.5rem, 1fr));
                    overflow-x: auto;
                }

                .training-tabs.training-learner-tabs button {
                    justify-content: center;
                }

                .training-learner-access {
                    align-items: stretch;
                    flex-direction: column;
                }

                .training-learner-access label {
                    width: 100%;
                }

                .training-learner-section,
                .training-results-section {
                    min-height: 28rem;
                    padding: 1rem;
                }

                .training-learning-path {
                    grid-template-columns: repeat(4, minmax(7rem, 1fr));
                    overflow-x: auto;
                    padding-inline: 1rem;
                }

                .training-learner-module,
                .training-certificate-card {
                    grid-template-columns: auto minmax(0, 1fr);
                }

                .training-learner-module-actions,
                .training-certificate-actions {
                    grid-column: 1 / -1;
                    align-items: stretch;
                    justify-content: flex-end;
                    flex-direction: row;
                    flex-wrap: wrap;
                }

                .training-result-hero,
                .training-result-grid {
                    grid-template-columns: 1fr;
                }

                .training-score-ring {
                    justify-self: center;
                }

                .training-result-summary {
                    text-align: center;
                }

                .training-result-summary .training-state-pill {
                    margin-inline: auto;
                }

                .training-result-actions {
                    grid-column: auto;
                    justify-content: stretch;
                }

                .training-result-actions button {
                    min-width: 0;
                }

                .training-certificate-actions {
                    align-items: center;
                }

                .training-learner-dialog footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .training-video-dialog .training-video-frame {
                    min-height: 18rem;
                }

                .training-tabs button {
                    justify-content: flex-start;
                    padding-inline: 1rem;
                }

                .training-filter-button,
                .training-primary-button,
                .training-secondary-button {
                    width: 100%;
                }
            }
        </style>
    @endpush

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-training-screen]');

                if (!root) {
                    return;
                }

                const buttons = root.querySelectorAll('[data-role-button]');
                const panels = root.querySelectorAll('[data-role-panel]');
                const programButtons = root.querySelectorAll('[data-program-tab]');
                const programPanels = root.querySelectorAll('[data-program-panel]');
                const programSelector = root.querySelector('[data-program-selector]');
                const programCarouselViewport = root.querySelector('[data-program-carousel-viewport]');
                const programCarouselTrack = root.querySelector('.training-program-carousel-track');
                const programCarouselPrevious = root.querySelector('[data-program-carousel-previous]');
                const programCarouselNext = root.querySelector('[data-program-carousel-next]');
                const newProgramButton = root.querySelector('[data-new-program]');
                const moduleItems = root.querySelectorAll('[data-module-item]');
                const moduleCount = root.querySelector('[data-module-count]');
                const moduleEditor = root.querySelector('.training-module-editor');
                const chapterVideoInputs = root.querySelectorAll('[data-chapter-video]');
                const programModal = root.querySelector('[data-program-modal]');
                const programForm = root.querySelector('[data-program-form]');
                const programModalTitle = root.querySelector('[data-program-modal-title]');
                const programSubmitLabel = root.querySelector('[data-program-submit-label]');
                const trainingAssignmentModal = root.querySelector('[data-training-assignment-modal]');
                const trainingAssignmentForm = root.querySelector('[data-training-assignment-form]');
                const assignmentStudent = root.querySelector('[data-assignment-student]');
                const assignmentStudentSummary = root.querySelector('[data-assignment-student-summary]');
                const assignmentStudentPicker = root.querySelector('[data-assignment-student-picker]');
                const assignmentStudentSelect = root.querySelector('[data-assignment-student-select]');
                const assignmentStartDate = root.querySelector('[data-assignment-start-date]');
                const assignmentSelectAll = root.querySelector('[data-assignment-select-all]');
                const assignmentPrograms = root.querySelectorAll('[data-assignment-program]');
                const assignmentCount = root.querySelector('[data-assignment-count]');
                const assignmentConfirm = root.querySelector('[data-confirm-training-assignment]');
                const employmentStatusControls = root.querySelectorAll('[data-employment-control]');
                const examEditorModal = root.querySelector('[data-exam-editor-modal]');
                const examEditorForm = root.querySelector('[data-exam-editor-form]');
                const examQuestionEditor = root.querySelector('[data-exam-question-editor]');
                const examEditorCount = root.querySelector('[data-exam-editor-count]');
                const examViewModal = root.querySelector('[data-exam-view-modal]');
                const examPreview = root.querySelector('[data-exam-preview]');
                const examIndexView = root.querySelector('[data-exam-index-view]');
                const examCreateView = root.querySelector('[data-exam-create-view]');
                const newExamForm = root.querySelector('[data-new-exam-form]');
                const newExamModule = root.querySelector('[data-new-exam-module]');
                const newExamQuestions = root.querySelector('[data-new-exam-questions]');
                const newExamSummary = root.querySelector('[data-new-exam-summary]');
                const examTableBody = root.querySelector('[data-exam-table-body]');
                const examSaveFeedback = root.querySelector('[data-exam-save-feedback]');
                const learnerTabButtons = root.querySelectorAll('[data-learner-tab]');
                const learnerPanels = root.querySelectorAll('[data-learner-panel]');
                const learnerTaskRows = root.querySelectorAll('[data-learner-task]');
                const learnerTaskFilters = root.querySelectorAll('[data-task-filter]');
                const learnerTaskEmptyRow = root.querySelector('[data-task-empty-row]');
                const learnerTaskDialog = root.querySelector('[data-task-dialog]');
                const learnerTaskForm = root.querySelector('[data-task-submission-form]');
                const learnerTaskSubmissionView = root.querySelector('[data-task-submission-view]');
                const learnerVideoDialog = root.querySelector('[data-learner-video-dialog]');
                const learnerExamDialog = root.querySelector('[data-learner-exam-dialog]');
                const learnerExamForm = root.querySelector('[data-learner-exam-form]');
                const learnerExamQuestions = root.querySelector('[data-learner-exam-questions]');
                const learnerExamValidation = root.querySelector('[data-learner-exam-validation]');
                const resultReviewDialog = root.querySelector('[data-result-review-dialog]');
                const learnerSelector = root.querySelector('[data-learner-selector]');
                const openLearnerPanelButton = root.querySelector('[data-open-learner-panel]');
                const activeLearnerNameElements = root.querySelectorAll('[data-active-learner-name]');
                const programStorageKey = 'mezclaspro.training.programs.v1';
                const selectedProgramStorageKey = 'mezclaspro.training.selected-program.v1';
                const examStorageKey = 'mezclaspro.training.exams.v1';
                const assignmentStorageKey = 'mezclaspro.training.assignments.v1';
                const employmentStatusStorageKey = 'mezclaspro.training.employment-statuses.v1';
                const learnerStateStorageKey = 'mezclaspro.training.learner-state.v3';
                const learnerSelectionStorageKey = 'mezclaspro.training.selected-learner.v1';
                const examDefaults = @json(collect($exams)->keyBy('id')->all());
                const moduleDefaults = @json($modules);
                const learnerCurriculum = @json($learnerCurriculum);
                const defaultLearnerProfile = @json($defaultLearnerProfile);
                const learnerExamFallbacks = {
                    'induccion-procesos': {
                        id: 'induccion-procesos',
                        module: 'Procesos esenciales',
                        name: 'Evaluacion de procesos esenciales',
                        minimum: 80,
                        items: [
                            {
                                text: 'Que permite asegurar la trazabilidad de un proceso critico?',
                                options: ['Registrar cada control y su responsable.', 'Conservar solo el resultado final.', 'Omitir incidencias ya corregidas.'],
                                correct: 0,
                            },
                            {
                                text: 'Como debe ejecutarse una secuencia critica de preparacion?',
                                options: ['En el orden definido por el procedimiento.', 'En el orden que prefiera cada operador.', 'Sin documentar los controles intermedios.'],
                                correct: 0,
                            },
                            {
                                text: 'Que evidencia respalda el cumplimiento de una etapa?',
                                options: ['Un comentario verbal del operador.', 'El registro completo, fechado y verificable.', 'Una nota sin identificacion.'],
                                correct: 1,
                            },
                        ],
                    },
                    'induccion-certificacion': {
                        id: 'induccion-certificacion',
                        module: 'Cierre y certificacion',
                        name: 'Evaluacion final de certificacion',
                        minimum: 80,
                        items: [
                            {
                                text: 'Que debe comprobarse antes de cerrar el programa?',
                                options: ['Que todos los requisitos y evidencias esten completos.', 'Que exista al menos un video abierto.', 'Que el alumno haya solicitado una excepcion.'],
                                correct: 0,
                            },
                            {
                                text: 'Cuando se habilita el certificado?',
                                options: ['Al iniciar el ultimo modulo.', 'Al completar tareas y aprobar todos los examenes.', 'Al registrar al alumno.'],
                                correct: 1,
                            },
                            {
                                text: 'Cual es el objetivo del expediente final?',
                                options: ['Respaldar de forma verificable la terminacion del programa.', 'Reemplazar los resultados de los examenes.', 'Eliminar el historial de avance.'],
                                correct: 0,
                            },
                        ],
                    },
                };
                let examRecords = readStoredExams();
                let editingExam = null;
                let creatingExam = null;
                let assignmentTargetRow = null;
                let activeLearnerTaskRow = null;
                let activeLearnerExamModule = null;
                let currentLearnerObligation = null;
                let learnerPlaybackTimer = null;
                let learnerPlaybackProgress = 0;
                hydrateLearnerSelectorFromAssignments();
                restoreLearnerSelection();
                let activeLearnerId = learnerSelector?.value || defaultLearnerProfile.id || 'default';
                let activeLearnerProfile = readSelectedLearnerProfile();
                let learnerState = readLearnerState();

                const laboratoryCarousel = root.querySelector('[data-personnel-laboratory-carousel]');
                const laboratoryPrevious = root.querySelector('[data-personnel-laboratory-previous]');
                const laboratoryNext = root.querySelector('[data-personnel-laboratory-next]');

                if (laboratoryCarousel && laboratoryPrevious && laboratoryNext) {
                    const updateLaboratoryNavigation = () => {
                        const maximumScroll = Math.max(0, laboratoryCarousel.scrollWidth - laboratoryCarousel.clientWidth);
                        laboratoryPrevious.disabled = laboratoryCarousel.scrollLeft <= 1;
                        laboratoryNext.disabled = laboratoryCarousel.scrollLeft >= maximumScroll - 1;
                    };

                    const moveLaboratoryCarousel = (direction) => {
                        laboratoryCarousel.scrollBy({ left: direction * 300, behavior: 'smooth' });
                    };

                    laboratoryPrevious.addEventListener('click', () => moveLaboratoryCarousel(-1));
                    laboratoryNext.addEventListener('click', () => moveLaboratoryCarousel(1));
                    laboratoryCarousel.addEventListener('scroll', updateLaboratoryNavigation, { passive: true });
                    window.addEventListener('resize', updateLaboratoryNavigation);

                    laboratoryCarousel
                        .querySelector('[data-selected-laboratory]')
                        ?.scrollIntoView({ block: 'nearest', inline: 'center' });
                    requestAnimationFrame(updateLaboratoryNavigation);
                }

                function updateProgramCarouselNavigation() {
                    if (!programCarouselViewport || !programCarouselPrevious || !programCarouselNext) {
                        return;
                    }

                    const maximumScroll = Math.max(0, programCarouselViewport.scrollWidth - programCarouselViewport.clientWidth);

                    programCarouselPrevious.disabled = programCarouselViewport.scrollLeft <= 1;
                    programCarouselNext.disabled = programCarouselViewport.scrollLeft >= maximumScroll - 1;
                }

                function moveProgramCarousel(direction) {
                    if (!programCarouselViewport) {
                        return;
                    }

                    const distance = Math.max(280, Math.round(programCarouselViewport.clientWidth * 0.72));

                    programCarouselViewport.scrollBy({ left: direction * distance, behavior: 'smooth' });
                }

                function revealProgramCard(card) {
                    if (!programCarouselViewport || !card) {
                        return;
                    }

                    const cardStart = card.offsetLeft;
                    const cardEnd = cardStart + card.offsetWidth;
                    const viewportStart = programCarouselViewport.scrollLeft;
                    const viewportEnd = viewportStart + programCarouselViewport.clientWidth;

                    if (cardStart < viewportStart) {
                        programCarouselViewport.scrollTo({ left: cardStart, behavior: 'smooth' });
                    } else if (cardEnd > viewportEnd) {
                        programCarouselViewport.scrollTo({ left: cardEnd - programCarouselViewport.clientWidth, behavior: 'smooth' });
                    }
                }

                if (programCarouselViewport && programCarouselPrevious && programCarouselNext) {
                    programCarouselPrevious.addEventListener('click', function() {
                        moveProgramCarousel(-1);
                    });
                    programCarouselNext.addEventListener('click', function() {
                        moveProgramCarousel(1);
                    });
                    programCarouselViewport.addEventListener('scroll', updateProgramCarouselNavigation, { passive: true });
                    window.addEventListener('resize', updateProgramCarouselNavigation);
                    requestAnimationFrame(updateProgramCarouselNavigation);
                }

                function readStoredPrograms() {
                    try {
                        const storedPrograms = JSON.parse(window.localStorage.getItem(programStorageKey) || '{}');

                        return storedPrograms && typeof storedPrograms === 'object' && !Array.isArray(storedPrograms)
                            ? storedPrograms
                            : {};
                    } catch (error) {
                        return {};
                    }
                }

                function readStoredAssignments() {
                    try {
                        const storedAssignments = JSON.parse(window.localStorage.getItem(assignmentStorageKey) || '{}');

                        return storedAssignments && typeof storedAssignments === 'object' && !Array.isArray(storedAssignments)
                            ? storedAssignments
                            : {};
                    } catch (error) {
                        return {};
                    }
                }

                function persistAssignments(assignments) {
                    try {
                        window.localStorage.setItem(assignmentStorageKey, JSON.stringify(assignments));
                    } catch (error) {
                        // Keep the assignment visible for this session when storage is unavailable.
                    }
                }

                function readStoredEmploymentStatuses() {
                    try {
                        const storedStatuses = JSON.parse(window.localStorage.getItem(employmentStatusStorageKey) || '{}');

                        return storedStatuses && typeof storedStatuses === 'object' && !Array.isArray(storedStatuses)
                            ? storedStatuses
                            : {};
                    } catch (error) {
                        return {};
                    }
                }

                function persistEmploymentStatuses(statuses) {
                    try {
                        window.localStorage.setItem(employmentStatusStorageKey, JSON.stringify(statuses));
                    } catch (error) {
                        // Keep the selected status visible for this session when storage is unavailable.
                    }
                }

                function setEmploymentStatus(control, status) {
                    const select = control ? control.querySelector('[data-student-status]') : null;
                    const normalizedStatus = status === 'inactive' ? 'inactive' : 'hired';

                    if (!control || !select) {
                        return;
                    }

                    select.value = normalizedStatus;
                    control.classList.toggle('is-hired', normalizedStatus === 'hired');
                    control.classList.toggle('is-inactive', normalizedStatus === 'inactive');
                }

                function applyStoredEmploymentStatuses() {
                    const storedStatuses = readStoredEmploymentStatuses();

                    employmentStatusControls.forEach(function(control) {
                        const row = control.closest('[data-student-row]');
                        const select = control.querySelector('[data-student-status]');

                        if (!row || !select) {
                            return;
                        }

                        setEmploymentStatus(control, storedStatuses[row.dataset.studentName] || select.value);
                    });
                }

                function mergeAssignments(currentAssignments, newAssignments) {
                    const mergedAssignments = Array.isArray(currentAssignments)
                        ? currentAssignments.map(function(assignment) { return Object.assign({}, assignment); })
                        : [];

                    newAssignments.forEach(function(newAssignment) {
                        const existingAssignment = mergedAssignments.find(function(assignment) {
                            return assignment.programId === newAssignment.programId;
                        });

                        if (!existingAssignment) {
                            mergedAssignments.push(newAssignment);
                            return;
                        }

                        existingAssignment.modules = Array.from(new Set(
                            (existingAssignment.modules || []).concat(newAssignment.modules || [])
                        ));
                        existingAssignment.allModules = existingAssignment.allModules || newAssignment.allModules;
                        existingAssignment.startDate = existingAssignment.startDate || newAssignment.startDate;
                    });

                    return mergedAssignments;
                }

                function createAssignedProgramElement(assignment) {
                    const progress = Math.min(100, Math.max(0, Number(assignment.progress) || 0));
                    const program = document.createElement('div');
                    const heading = document.createElement('div');
                    const name = document.createElement('span');
                    const percentage = document.createElement('strong');
                    const detail = document.createElement('small');
                    const track = document.createElement('span');
                    const fill = document.createElement('span');

                    program.className = 'training-current-program';
                    program.dataset.currentProgramId = assignment.programId;
                    program.dataset.addedAssignment = '';
                    heading.className = 'training-current-program-heading';
                    name.textContent = assignment.programName;
                    percentage.textContent = progress + '%';
                    heading.append(name, percentage);

                    detail.className = 'training-current-program-detail';
                    detail.textContent = assignment.allModules
                        ? 'Programa completo'
                        : (assignment.modules || []).join(', ');

                    track.className = 'training-progress-track';
                    track.setAttribute('aria-label', 'Progreso: ' + progress + '%');
                    fill.style.width = progress + '%';
                    track.append(fill);
                    program.append(heading, detail, track);

                    return program;
                }

                function formatAssignmentStartDate(value) {
                    const parts = String(value || '').split('-');

                    return parts.length === 3
                        ? parts[2] + '/' + parts[1] + '/' + parts[0]
                        : (value || 'Sin fecha');
                }

                function createAssignedStartDateElement(assignment) {
                    const startDate = document.createElement('span');

                    startDate.dataset.startDateProgramId = assignment.programId;
                    startDate.dataset.addedStartDate = '';
                    startDate.textContent = formatAssignmentStartDate(assignment.startDate);

                    return startDate;
                }

                function renderAssignmentsForRow(row, assignments) {
                    const currentPrograms = row ? row.querySelector('[data-current-programs]') : null;
                    const startDates = row ? row.querySelector('[data-training-start-dates]') : null;

                    if (!currentPrograms) {
                        return;
                    }

                    currentPrograms.querySelectorAll('[data-added-assignment]').forEach(function(program) {
                        program.remove();
                    });

                    startDates?.querySelectorAll('[data-added-start-date]').forEach(function(startDate) {
                        startDate.remove();
                    });

                    (assignments || []).forEach(function(assignment) {
                        const alreadyAssigned = Array.from(
                            currentPrograms.querySelectorAll('[data-current-program-id]')
                        ).some(function(program) {
                            return program.dataset.currentProgramId === assignment.programId;
                        });

                        if (!alreadyAssigned) {
                            currentPrograms.append(createAssignedProgramElement(assignment));
                        }

                        const alreadyHasStartDate = startDates && Array.from(
                            startDates.querySelectorAll('[data-start-date-program-id]')
                        ).some(function(startDate) {
                            return startDate.dataset.startDateProgramId === assignment.programId;
                        });

                        if (startDates && !alreadyHasStartDate) {
                            startDates.querySelector('.training-start-date-list')?.append(
                                createAssignedStartDateElement(assignment)
                            );
                        }
                    });

                    const hasPrograms = currentPrograms.querySelector('[data-current-program-id]');
                    const emptyProgram = currentPrograms.querySelector('[data-empty-current-program]');

                    if (hasPrograms && emptyProgram) {
                        emptyProgram.remove();
                    }

                    if (startDates?.querySelector('[data-start-date-program-id]')) {
                        startDates.querySelector('[data-empty-start-date]')?.remove();
                    }

                }

                function applyStoredAssignments() {
                    const storedAssignments = readStoredAssignments();

                    root.querySelectorAll('[data-student-row]').forEach(function(row) {
                        renderAssignmentsForRow(row, storedAssignments[row.dataset.studentName] || []);
                    });
                }

                function syncProgramCheckbox(program) {
                    const programCheckbox = program.querySelector('[data-assignment-program-checkbox]');
                    const moduleCheckboxes = Array.from(program.querySelectorAll('[data-assignment-module-checkbox]'));
                    const checkedModules = moduleCheckboxes.filter(function(checkbox) { return checkbox.checked; }).length;

                    if (!programCheckbox) {
                        return;
                    }

                    programCheckbox.checked = moduleCheckboxes.length > 0 && checkedModules === moduleCheckboxes.length;
                    programCheckbox.indeterminate = checkedModules > 0 && checkedModules < moduleCheckboxes.length;
                }

                function updateAssignmentSelection() {
                    const moduleCheckboxes = Array.from(root.querySelectorAll('[data-assignment-module-checkbox]'));
                    const checkedModules = moduleCheckboxes.filter(function(checkbox) { return checkbox.checked; }).length;

                    if (assignmentSelectAll) {
                        assignmentSelectAll.checked = moduleCheckboxes.length > 0 && checkedModules === moduleCheckboxes.length;
                        assignmentSelectAll.indeterminate = checkedModules > 0 && checkedModules < moduleCheckboxes.length;
                    }

                    if (assignmentCount) {
                        assignmentCount.textContent = checkedModules + (checkedModules === 1
                            ? ' modulo seleccionado'
                            : ' modulos seleccionados');
                    }

                    if (assignmentConfirm) {
                        assignmentConfirm.disabled = checkedModules === 0
                            || !assignmentTargetRow
                            || !assignmentStartDate?.value;
                    }
                }

                function currentAssignmentDate() {
                    const currentDate = new Date();

                    currentDate.setMinutes(currentDate.getMinutes() - currentDate.getTimezoneOffset());

                    return currentDate.toISOString().slice(0, 10);
                }

                function resetAssignmentSelection() {
                    root.querySelectorAll('[data-assignment-program-checkbox], [data-assignment-module-checkbox]').forEach(function(checkbox) {
                        checkbox.checked = false;
                        checkbox.indeterminate = false;
                    });

                    if (assignmentSelectAll) {
                        assignmentSelectAll.checked = false;
                        assignmentSelectAll.indeterminate = false;
                    }

                    updateAssignmentSelection();
                }

                function collectAssignments() {
                    return Array.from(assignmentPrograms).reduce(function(assignments, program) {
                        const moduleCheckboxes = Array.from(program.querySelectorAll('[data-assignment-module-checkbox]'));
                        const selectedModules = moduleCheckboxes.filter(function(checkbox) {
                            return checkbox.checked;
                        }).map(function(checkbox) {
                            return checkbox.dataset.moduleLabel;
                        });

                        if (!selectedModules.length) {
                            return assignments;
                        }

                        assignments.push({
                            programId: program.dataset.programId,
                            programName: program.dataset.programName,
                            modules: selectedModules,
                            allModules: selectedModules.length === moduleCheckboxes.length,
                            progress: 0,
                            startDate: assignmentStartDate?.value || currentAssignmentDate(),
                        });

                        return assignments;
                    }, []);
                }

                function closeTrainingAssignment() {
                    if (trainingAssignmentModal && trainingAssignmentModal.open) {
                        trainingAssignmentModal.close();
                    }

                    assignmentTargetRow = null;
                }

                function openTrainingAssignment(button) {
                    const row = button.closest('[data-student-row]');

                    if (!row || !trainingAssignmentModal) {
                        return;
                    }

                    assignmentTargetRow = row;
                    resetAssignmentSelection();

                    if (assignmentStudentSummary) {
                        assignmentStudentSummary.hidden = false;
                    }

                    if (assignmentStudentPicker) {
                        assignmentStudentPicker.hidden = true;
                    }

                    if (assignmentStudentSelect) {
                        assignmentStudentSelect.value = row.dataset.studentIndex || '';
                    }

                    if (assignmentStudent) {
                        assignmentStudent.textContent = row.dataset.studentName;
                    }

                    if (assignmentStartDate) {
                        assignmentStartDate.value = currentAssignmentDate();
                    }

                    trainingAssignmentModal.showModal();

                    if (assignmentSelectAll) {
                        assignmentSelectAll.focus();
                    }
                }

                function openNewTrainingAssignment() {
                    if (!trainingAssignmentModal || !assignmentStudentSelect) {
                        return;
                    }

                    assignmentTargetRow = null;
                    assignmentStudentSelect.value = '';

                    if (assignmentStartDate) {
                        assignmentStartDate.value = currentAssignmentDate();
                    }

                    resetAssignmentSelection();

                    if (assignmentStudent) {
                        assignmentStudent.textContent = '';
                    }

                    if (assignmentStudentSummary) {
                        assignmentStudentSummary.hidden = true;
                    }

                    if (assignmentStudentPicker) {
                        assignmentStudentPicker.hidden = false;
                    }

                    trainingAssignmentModal.showModal();
                    assignmentStudentSelect.focus();
                }

                function cloneExam(exam) {
                    return JSON.parse(JSON.stringify(exam));
                }

                function normaliseExam(exam) {
                    const sourceItems = Array.isArray(exam.items) && exam.items.length ? exam.items : [];
                    const items = sourceItems.map(function(question) {
                        const questionType = ['single', 'boolean', 'multiple'].includes(question.type)
                            ? question.type
                            : 'single';
                        const options = Array.isArray(question.options)
                            ? question.options.map(function(option) { return String(option ?? ''); })
                            : [];

                        while (options.length < 2) {
                            options.push('');
                        }

                        let correct;

                        if (questionType === 'multiple') {
                            const selectedAnswers = Array.isArray(question.correct) ? question.correct : [question.correct];

                            correct = Array.from(new Set(selectedAnswers.map(function(answer) {
                                return Number.parseInt(answer, 10);
                            }).filter(function(answer) {
                                return Number.isInteger(answer) && answer >= 0 && answer < options.length;
                            })));

                            if (!correct.length) {
                                correct = [0];
                            }
                        } else {
                            const selectedAnswer = Array.isArray(question.correct) ? question.correct[0] : question.correct;

                            correct = Number.parseInt(selectedAnswer, 10);

                            if (!Number.isInteger(correct) || correct < 0 || correct >= options.length) {
                                correct = 0;
                            }
                        }

                        const parsedPoints = Number.parseInt(question.points, 10);

                        return {
                            text: String(question.text ?? ''),
                            type: questionType,
                            points: Number.isInteger(parsedPoints) && parsedPoints > 0 ? parsedPoints : 25,
                            options: options,
                            correct: correct,
                        };
                    });
                    const parsedMinimum = Number.parseInt(exam.minimum, 10);
                    const parsedDuration = Number.parseInt(exam.duration, 10);
                    const parsedAttempts = Number.parseInt(exam.attempts, 10);
                    const moduleName = String(exam.module ?? '');

                    return Object.assign({}, exam, {
                        module: moduleName,
                        name: String(exam.name ?? ('Evaluacion de ' + moduleName.toLowerCase())),
                        instructions: String(exam.instructions ?? 'Lee cuidadosamente cada pregunta y selecciona la respuesta correcta.'),
                        minimum: Number.isInteger(parsedMinimum) ? Math.min(100, Math.max(0, parsedMinimum)) : 0,
                        duration: Number.isInteger(parsedDuration) && parsedDuration > 0 ? parsedDuration : 20,
                        attempts: Number.isInteger(parsedAttempts) && parsedAttempts > 0 ? parsedAttempts : 2,
                        randomize: exam.randomize === true,
                        questions: items.length,
                        items: items,
                    });
                }

                function readStoredExams() {
                    let storedExams = {};

                    try {
                        const parsedExams = JSON.parse(window.localStorage.getItem(examStorageKey) || '{}');

                        if (parsedExams && typeof parsedExams === 'object' && !Array.isArray(parsedExams)) {
                            storedExams = parsedExams;
                        }
                    } catch (error) {
                        storedExams = {};
                    }

                    const examIds = Array.from(new Set(Object.keys(examDefaults).concat(Object.keys(storedExams))));

                    return examIds.reduce(function(records, examId) {
                        const defaultExam = examDefaults[examId] || {};
                        const savedExam = storedExams[examId];
                        const exam = savedExam && typeof savedExam === 'object' && !Array.isArray(savedExam)
                            ? Object.assign({}, defaultExam, savedExam, { id: examId })
                            : Object.assign({}, defaultExam, { id: examId });

                        records[examId] = normaliseExam(exam);

                        return records;
                    }, {});
                }

                function persistExams() {
                    try {
                        window.localStorage.setItem(examStorageKey, JSON.stringify(examRecords));
                    } catch (error) {
                        // The current session remains updated when browser storage is unavailable.
                    }
                }

                function createExamElement(tagName, className, textContent) {
                    const element = document.createElement(tagName);

                    if (className) {
                        element.className = className;
                    }

                    if (typeof textContent === 'string') {
                        element.textContent = textContent;
                    }

                    return element;
                }

                function findExamTableRow(examId) {
                    return Array.from(root.querySelectorAll('[data-exam-id]')).find(function(item) {
                        return item.dataset.examId === examId;
                    });
                }

                function createExamActionButton(action, exam) {
                    const button = createExamElement('button', 'training-icon-button');
                    const isEdit = action === 'edit';

                    button.type = 'button';
                    button.dataset[isEdit ? 'editExam' : 'viewExam'] = exam.id;
                    button.title = isEdit ? 'Editar examen' : 'Ver examen';
                    button.setAttribute('aria-label', (isEdit ? 'Editar ' : 'Ver ') + exam.name);
                    button.appendChild(createExamElement('i', isEdit ? 'fa-solid fa-pen' : 'fa-regular fa-eye'));

                    return button;
                }

                function ensureExamTableRow(exam) {
                    const existingRow = findExamTableRow(exam.id);

                    if (existingRow || !examTableBody) {
                        return existingRow;
                    }

                    const row = createExamElement('tr');
                    const nameCell = createExamElement('td', '', exam.name);
                    const moduleCell = createExamElement('td', '', exam.module);
                    const countCell = createExamElement('td', '', String(exam.items.length));
                    const minimumCell = createExamElement('td', '', exam.minimum + '%');
                    const statusCell = createExamElement('td');
                    const editCell = createExamElement('td');
                    const viewCell = createExamElement('td');
                    const status = createExamElement('span', 'training-program-status is-' + exam.tone, exam.status);
                    const emptyRow = examTableBody.querySelector('[data-program-empty-row]');

                    row.dataset.programRow = exam.projectId;
                    row.dataset.examId = exam.id;
                    nameCell.dataset.examName = '';
                    moduleCell.dataset.examModule = '';
                    countCell.dataset.examQuestionCount = '';
                    minimumCell.dataset.examMinimum = '';
                    status.dataset.examStatus = '';
                    statusCell.appendChild(status);
                    editCell.appendChild(createExamActionButton('edit', exam));
                    viewCell.appendChild(createExamActionButton('view', exam));
                    row.append(nameCell, moduleCell, countCell, minimumCell, statusCell, editCell, viewCell);
                    examTableBody.insertBefore(row, emptyRow || null);

                    return row;
                }

                function updateExamTableRow(exam) {
                    const row = ensureExamTableRow(exam) || Array.from(root.querySelectorAll('[data-exam-id]')).find(function(item) {
                        return item.dataset.examId === exam.id;
                    });

                    if (!row) {
                        return null;
                    }

                    row.dataset.programRow = exam.projectId;
                    row.querySelector('[data-exam-name]').textContent = exam.name;
                    row.querySelector('[data-exam-module]').textContent = exam.module;
                    row.querySelector('[data-exam-question-count]').textContent = exam.items.length;
                    row.querySelector('[data-exam-minimum]').textContent = exam.minimum + '%';

                    const status = row.querySelector('[data-exam-status]');

                    status.textContent = exam.status;
                    status.classList.remove('is-active', 'is-draft', 'is-inactive');
                    status.classList.add('is-' + exam.tone);

                    const editButton = row.querySelector('[data-edit-exam]');
                    const viewButton = row.querySelector('[data-view-exam]');

                    editButton.setAttribute('aria-label', 'Editar ' + exam.name);
                    viewButton.setAttribute('aria-label', 'Ver ' + exam.name);

                    return row;
                }

                function showExamSaveFeedback(exam, saveMode) {
                    if (!examSaveFeedback) {
                        return;
                    }

                    const message = saveMode === 'published'
                        ? 'El examen "' + exam.name + '" se publico y se agrego a Examenes.'
                        : 'El borrador "' + exam.name + '" se agrego a Examenes.';

                    examSaveFeedback.replaceChildren(
                        createExamElement('i', 'fa-solid fa-circle-check'),
                        createExamElement('span', '', message)
                    );
                    examSaveFeedback.hidden = false;
                }

                function buildExamQuestionEditor(question, questionIndex) {
                    const article = createExamElement('article', 'training-exam-question-editor');
                    const heading = createExamElement('div', 'training-exam-question-heading');
                    const questionTypeLabels = {
                        single: 'Opcion multiple',
                        boolean: 'Verdadero / Falso',
                        multiple: 'Seleccion multiple',
                    };
                    const title = createExamElement(
                        'strong',
                        '',
                        'Pregunta ' + (questionIndex + 1) + ' | ' + questionTypeLabels[question.type]
                    );
                    const removeQuestion = createExamElement('button', 'training-icon-button');

                    removeQuestion.type = 'button';
                    removeQuestion.dataset.removeExamQuestion = String(questionIndex);
                    removeQuestion.title = 'Quitar pregunta';
                    removeQuestion.setAttribute('aria-label', 'Quitar pregunta ' + (questionIndex + 1));
                    removeQuestion.disabled = editingExam.items.length === 1;
                    removeQuestion.appendChild(createExamElement('i', 'fa-solid fa-trash-can'));
                    heading.append(title, removeQuestion);

                    const questionField = createExamElement('label', 'training-exam-question-field');
                    const questionLabel = createExamElement('span', '', 'Texto de la pregunta');
                    const questionInput = createExamElement('textarea');

                    questionInput.value = question.text;
                    questionInput.rows = 2;
                    questionInput.required = true;
                    questionInput.maxLength = 500;
                    questionInput.dataset.examQuestionText = String(questionIndex);
                    questionField.append(questionLabel, questionInput);

                    const options = createExamElement('div', 'training-exam-options');
                    options.appendChild(createExamElement('span', 'training-exam-options-label', 'Opciones de respuesta. Marca la opcion correcta.'));

                    question.options.forEach(function(option, optionIndex) {
                        const optionRow = createExamElement('div', 'training-exam-option-row');
                        const correctOption = createExamElement('input');
                        const optionInput = createExamElement('input');
                        const removeOption = createExamElement('button', 'training-icon-button');
                        const isMultiple = question.type === 'multiple';

                        correctOption.type = isMultiple ? 'checkbox' : 'radio';
                        correctOption.name = 'exam_correct_' + questionIndex;
                        correctOption.checked = isMultiple
                            ? question.correct.includes(optionIndex)
                            : question.correct === optionIndex;
                        correctOption.required = !isMultiple;
                        correctOption.dataset.examCorrectOption = String(questionIndex);
                        correctOption.dataset.optionIndex = String(optionIndex);
                        correctOption.setAttribute('aria-label', 'Marcar opcion ' + (optionIndex + 1) + ' como correcta');

                        optionInput.type = 'text';
                        optionInput.value = option;
                        optionInput.required = true;
                        optionInput.maxLength = 250;
                        optionInput.readOnly = question.type === 'boolean';
                        optionInput.dataset.examOptionText = String(questionIndex);
                        optionInput.dataset.optionIndex = String(optionIndex);
                        optionInput.setAttribute('aria-label', 'Opcion ' + (optionIndex + 1) + ' de la pregunta ' + (questionIndex + 1));

                        removeOption.type = 'button';
                        removeOption.dataset.removeExamOption = String(questionIndex);
                        removeOption.dataset.optionIndex = String(optionIndex);
                        removeOption.title = 'Quitar opcion';
                        removeOption.setAttribute('aria-label', 'Quitar opcion ' + (optionIndex + 1));
                        removeOption.disabled = question.options.length <= 2 || question.type === 'boolean';
                        removeOption.appendChild(createExamElement('i', 'fa-solid fa-xmark'));

                        optionRow.append(correctOption, optionInput, removeOption);
                        options.appendChild(optionRow);
                    });

                    const addOption = createExamElement('button', 'training-secondary-button training-exam-add-option');
                    addOption.type = 'button';
                    addOption.dataset.addExamOption = String(questionIndex);
                    addOption.hidden = question.type === 'boolean';
                    addOption.append(
                        createExamElement('i', 'fa-solid fa-plus'),
                        createExamElement('span', '', 'Agregar opcion')
                    );
                    options.appendChild(addOption);
                    article.append(heading, questionField, options);

                    return article;
                }

                function renderExamQuestionEditor() {
                    if (!examQuestionEditor || !editingExam) {
                        return;
                    }

                    examQuestionEditor.replaceChildren();

                    editingExam.items.forEach(function(question, questionIndex) {
                        examQuestionEditor.appendChild(buildExamQuestionEditor(question, questionIndex));
                    });

                    if (examEditorCount) {
                        examEditorCount.textContent = editingExam.items.length + (editingExam.items.length === 1 ? ' pregunta' : ' preguntas');
                    }
                }

                function renderExamPreview(exam) {
                    if (!examPreview) {
                        return;
                    }

                    examPreview.replaceChildren();

                    const heading = createExamElement('div', 'training-exam-preview-heading');
                    const headingText = createExamElement('div');
                    const title = createExamElement('h4', '', exam.name || exam.module);
                    const description = createExamElement(
                        'p',
                        '',
                        exam.module + ' | ' + exam.items.length + (exam.items.length === 1 ? ' pregunta' : ' preguntas') +
                            ' | Minimo: ' + exam.minimum + '% | ' + exam.duration + ' minutos | ' + exam.attempts + ' intentos'
                    );
                    const status = createExamElement('span', 'training-program-status is-' + exam.tone, exam.status);
                    const instructions = createExamElement('p', 'training-exam-preview-instructions', exam.instructions);

                    headingText.append(title, description);
                    heading.append(headingText, status);

                    const list = createExamElement('div', 'training-exam-preview-list');

                    exam.items.forEach(function(question, questionIndex) {
                        const questionCard = createExamElement('article', 'training-exam-preview-question');
                        const fieldset = createExamElement('fieldset');
                        const legend = createExamElement('legend', '', (questionIndex + 1) + '. ' + question.text);

                        fieldset.disabled = true;
                        fieldset.appendChild(legend);

                        question.options.forEach(function(option, optionIndex) {
                            const optionLabel = createExamElement('label', 'training-exam-preview-option');
                            const optionRadio = createExamElement('input');

                            optionRadio.type = question.type === 'multiple' ? 'checkbox' : 'radio';
                            optionRadio.name = 'preview_' + exam.id + '_' + questionIndex;
                            optionRadio.value = String(optionIndex);
                            optionLabel.append(optionRadio, createExamElement('span', '', option));
                            fieldset.appendChild(optionLabel);
                        });

                        questionCard.appendChild(fieldset);
                        list.appendChild(questionCard);
                    });

                    examPreview.append(heading, instructions, list);
                }

                function closeExamEditor() {
                    if (examEditorModal && examEditorModal.open) {
                        examEditorModal.close();
                    }

                    editingExam = null;
                }

                function closeExamView() {
                    if (examViewModal && examViewModal.open) {
                        examViewModal.close();
                    }
                }

                function openExamEditor(examId) {
                    const exam = examRecords[examId];

                    if (!exam || !examEditorModal || !examEditorForm) {
                        return;
                    }

                    editingExam = cloneExam(exam);
                    examEditorForm.elements.exam_id.value = exam.id;
                    examEditorForm.elements.module.value = exam.module;
                    examEditorForm.elements.minimum.value = exam.minimum;
                    renderExamQuestionEditor();
                    examEditorModal.showModal();
                    examEditorForm.elements.module.focus();
                }

                function openExamView(examId) {
                    const exam = examRecords[examId];

                    if (!exam || !examViewModal) {
                        return;
                    }

                    renderExamPreview(exam);
                    examViewModal.showModal();
                }

                function createNewExamQuestion(type) {
                    const questionType = ['single', 'boolean', 'multiple'].includes(type) ? type : 'single';

                    return {
                        text: '',
                        type: questionType,
                        points: 100,
                        options: questionType === 'boolean' ? ['Verdadero', 'Falso'] : ['', ''],
                        correct: questionType === 'multiple' ? [0] : 0,
                    };
                }

                function rebalanceNewExamPoints() {
                    if (!creatingExam || !creatingExam.items.length) {
                        return;
                    }

                    const basePoints = Math.floor(100 / creatingExam.items.length);
                    let remainingPoints = 100 - (basePoints * creatingExam.items.length);

                    creatingExam.items.forEach(function(question) {
                        question.points = basePoints + (remainingPoints > 0 ? 1 : 0);
                        remainingPoints -= remainingPoints > 0 ? 1 : 0;
                    });
                }

                function updateNewExamSummary() {
                    if (!newExamSummary || !creatingExam) {
                        return;
                    }

                    const totalPoints = creatingExam.items.reduce(function(total, question) {
                        return total + (Number.parseInt(question.points, 10) || 0);
                    }, 0);

                    newExamSummary.textContent = creatingExam.items.length +
                        (creatingExam.items.length === 1 ? ' pregunta' : ' preguntas') +
                        ' | ' + totalPoints + ' puntos';
                }

                function createQuestionIconButton(iconClass, title, dataName, dataValue) {
                    const button = createExamElement('button', 'training-icon-button');

                    button.type = 'button';
                    button.title = title;
                    button.setAttribute('aria-label', title);
                    button.dataset[dataName] = String(dataValue);
                    button.appendChild(createExamElement('i', iconClass));

                    return button;
                }

                function buildNewExamQuestion(question, questionIndex) {
                    const card = createExamElement('article', 'training-new-question-card');
                    const toolbar = createExamElement('div', 'training-new-question-toolbar');
                    const handle = createExamElement('span', 'training-question-handle');
                    const number = createExamElement('span', 'training-question-number', String(questionIndex + 1));
                    const title = createExamElement('strong', 'training-question-toolbar-title', 'Pregunta ' + (questionIndex + 1));
                    const typeField = createExamElement('label', 'training-new-question-type');
                    const typeSelect = createExamElement('select');
                    const pointsField = createExamElement('label', 'training-new-question-points');
                    const pointsInput = createExamElement('input');
                    const actions = createExamElement('div', 'training-question-toolbar-actions');

                    handle.appendChild(createExamElement('i', 'fa-solid fa-grip-vertical'));
                    handle.setAttribute('aria-hidden', 'true');

                    typeField.appendChild(createExamElement('span', '', 'Tipo de pregunta'));
                    [
                        ['single', 'Opcion multiple'],
                        ['boolean', 'Verdadero / Falso'],
                        ['multiple', 'Seleccion multiple'],
                    ].forEach(function(optionData) {
                        const option = createExamElement('option', '', optionData[1]);

                        option.value = optionData[0];
                        option.selected = question.type === optionData[0];
                        typeSelect.appendChild(option);
                    });
                    typeSelect.dataset.newQuestionType = String(questionIndex);
                    typeField.appendChild(typeSelect);

                    pointsField.appendChild(createExamElement('span', '', 'Puntos'));
                    pointsInput.type = 'number';
                    pointsInput.min = '1';
                    pointsInput.max = '1000';
                    pointsInput.step = '1';
                    pointsInput.required = true;
                    pointsInput.value = String(question.points);
                    pointsInput.dataset.newQuestionPoints = String(questionIndex);
                    pointsField.appendChild(pointsInput);

                    actions.append(
                        createQuestionIconButton('fa-solid fa-pen', 'Editar pregunta ' + (questionIndex + 1), 'focusNewQuestion', questionIndex),
                        createQuestionIconButton('fa-regular fa-copy', 'Duplicar pregunta ' + (questionIndex + 1), 'duplicateNewQuestion', questionIndex),
                        createQuestionIconButton('fa-regular fa-trash-can', 'Eliminar pregunta ' + (questionIndex + 1), 'removeNewQuestion', questionIndex)
                    );

                    const removeQuestionButton = actions.querySelector('[data-remove-new-question]');

                    if (removeQuestionButton) {
                        removeQuestionButton.disabled = creatingExam.items.length === 1;
                    }

                    toolbar.append(handle, number, title, typeField, pointsField, actions);

                    const questionField = createExamElement('label', 'training-new-question-field');
                    const questionInput = createExamElement('textarea');

                    questionField.appendChild(createExamElement('span', '', 'Pregunta'));
                    questionInput.rows = 2;
                    questionInput.maxLength = 500;
                    questionInput.required = true;
                    questionInput.value = question.text;
                    questionInput.placeholder = 'Escribe la pregunta del examen';
                    questionInput.dataset.newQuestionText = String(questionIndex);
                    questionField.appendChild(questionInput);

                    const optionList = createExamElement('div', 'training-new-question-option-list');

                    question.options.forEach(function(option, optionIndex) {
                        const optionRow = createExamElement('div', 'training-new-question-option');
                        const answerControl = createExamElement('input');
                        const optionInput = createExamElement('input');
                        const optionActions = createExamElement('div', 'training-new-question-option-actions');
                        const isMultiple = question.type === 'multiple';
                        const isCorrect = isMultiple
                            ? question.correct.includes(optionIndex)
                            : question.correct === optionIndex;

                        optionRow.classList.toggle('is-correct', isCorrect);
                        answerControl.type = isMultiple ? 'checkbox' : 'radio';
                        answerControl.name = 'new_exam_correct_' + questionIndex;
                        answerControl.checked = isCorrect;
                        answerControl.dataset.newQuestionCorrect = String(questionIndex);
                        answerControl.dataset.optionIndex = String(optionIndex);
                        answerControl.setAttribute('aria-label', 'Marcar opcion ' + (optionIndex + 1) + ' como correcta');

                        optionInput.type = 'text';
                        optionInput.required = true;
                        optionInput.readOnly = question.type === 'boolean';
                        optionInput.maxLength = 250;
                        optionInput.value = option;
                        optionInput.placeholder = 'Opcion ' + (optionIndex + 1);
                        optionInput.dataset.newQuestionOption = String(questionIndex);
                        optionInput.dataset.optionIndex = String(optionIndex);

                        if (isCorrect) {
                            optionActions.appendChild(createExamElement('small', '', 'Respuesta correcta'));
                        }

                        const removeOption = createQuestionIconButton(
                            'fa-solid fa-xmark',
                            'Quitar opcion ' + (optionIndex + 1),
                            'removeNewQuestionOption',
                            questionIndex
                        );
                        removeOption.dataset.optionIndex = String(optionIndex);
                        removeOption.disabled = question.options.length <= 2 || question.type === 'boolean';
                        optionActions.appendChild(removeOption);
                        optionRow.append(answerControl, optionInput, optionActions);
                        optionList.appendChild(optionRow);
                    });

                    if (question.type !== 'boolean') {
                        const addOption = createExamElement('button', 'training-secondary-button training-new-question-add-option');

                        addOption.type = 'button';
                        addOption.dataset.addNewQuestionOption = String(questionIndex);
                        addOption.append(
                            createExamElement('i', 'fa-solid fa-plus'),
                            createExamElement('span', '', 'Agregar opcion')
                        );
                        optionList.appendChild(addOption);
                    }

                    card.append(toolbar, questionField, optionList);

                    return card;
                }

                function renderNewExamQuestions() {
                    if (!newExamQuestions || !creatingExam) {
                        return;
                    }

                    newExamQuestions.replaceChildren();

                    creatingExam.items.forEach(function(question, questionIndex) {
                        newExamQuestions.appendChild(buildNewExamQuestion(question, questionIndex));
                    });

                    updateNewExamSummary();
                }

                function populateNewExamModules(projectId) {
                    if (!newExamModule) {
                        return '';
                    }

                    newExamModule.replaceChildren();

                    moduleDefaults.filter(function(module) {
                        return module.project === projectId;
                    }).forEach(function(module) {
                        const option = createExamElement('option', '', module.stage + ' - ' + module.title);

                        option.value = module.title;
                        newExamModule.appendChild(option);
                    });

                    if (!newExamModule.options.length) {
                        const option = createExamElement('option', '', 'No hay modulos disponibles');

                        option.value = '';
                        option.disabled = true;
                        option.selected = true;
                        newExamModule.appendChild(option);
                    }

                    return newExamModule.value;
                }

                function openNewExamBuilder() {
                    if (!examIndexView || !examCreateView || !newExamForm || !programSelector) {
                        return;
                    }

                    if (examSaveFeedback) {
                        examSaveFeedback.hidden = true;
                    }

                    const projectId = programSelector.value;
                    const selectedProgram = programSelector.options[programSelector.selectedIndex];
                    const moduleName = populateNewExamModules(projectId);

                    creatingExam = {
                        id: '',
                        projectId: projectId,
                        project: selectedProgram ? selectedProgram.textContent.trim() : '',
                        module: moduleName,
                        name: moduleName ? 'Evaluacion de ' + moduleName.toLowerCase() : '',
                        instructions: 'Lee cuidadosamente cada pregunta y selecciona la respuesta correcta.',
                        minimum: 80,
                        duration: 20,
                        attempts: 2,
                        randomize: true,
                        status: 'Borrador',
                        tone: 'draft',
                        items: [createNewExamQuestion('single')],
                    };

                    newExamForm.elements.name.value = creatingExam.name;
                    newExamForm.elements.instructions.value = creatingExam.instructions;
                    newExamForm.elements.minimum.value = String(creatingExam.minimum);
                    newExamForm.elements.duration.value = String(creatingExam.duration);
                    newExamForm.elements.attempts.value = String(creatingExam.attempts);
                    newExamForm.elements.randomize.checked = creatingExam.randomize;
                    examIndexView.hidden = true;
                    examCreateView.hidden = false;

                    renderNewExamQuestions();
                    newExamModule.focus();
                }

                function closeNewExamBuilder() {
                    if (examIndexView) {
                        examIndexView.hidden = false;
                    }

                    if (examCreateView) {
                        examCreateView.hidden = true;
                    }

                    creatingExam = null;
                }

                function createExamId() {
                    return 'exam-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
                }

                function updateSelectedProgramName(programName) {
                    root.querySelectorAll('[data-selected-program-name]').forEach(function(element) {
                        element.textContent = programName;
                    });
                }

                function createProgramId(title) {
                    const slug = title.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-|-$/g, '')
                        .slice(0, 48) || 'programa';

                    return slug + '-' + Date.now().toString(36);
                }

                function ensureProgramOption(programId, title) {
                    if (!programSelector) {
                        return null;
                    }

                    let option = Array.from(programSelector.options).find(function(item) {
                        return item.value === programId;
                    });

                    if (!option) {
                        option = createExamElement('option', '', title);
                        option.value = programId;
                        programSelector.appendChild(option);
                    }

                    option.textContent = title;

                    return option;
                }

                function createProgramCarouselCard(programId, data) {
                    if (!programCarouselTrack) {
                        return null;
                    }

                    const card = createExamElement('article', 'training-program-carousel-card');
                    const selectButton = createExamElement('button', 'training-program-carousel-select');
                    const header = createExamElement('span', 'training-program-carousel-card-header');
                    const icon = createExamElement('span', 'training-program-carousel-icon');
                    const status = createExamElement('span', 'training-program-status');
                    const title = createExamElement('strong', '', data.title);
                    const description = createExamElement('span', 'training-program-carousel-description', data.description);
                    const meta = createExamElement('span', 'training-program-carousel-meta');
                    const owner = createExamElement('span');
                    const modules = createExamElement('span');
                    const editButton = createExamElement('button', 'training-program-carousel-edit');

                    card.dataset.programCard = '';
                    card.dataset.programId = programId;
                    selectButton.type = 'button';
                    selectButton.dataset.programCarouselItem = '';
                    selectButton.dataset.programId = programId;
                    selectButton.setAttribute('aria-pressed', 'false');
                    icon.appendChild(createExamElement('i', 'fa-regular fa-folder-open'));
                    status.dataset.programStatus = '';
                    title.dataset.programTitle = '';
                    description.dataset.programDescription = '';
                    owner.append(
                        createExamElement('small', '', 'Responsable'),
                        createExamElement('b', '', data.owner)
                    );
                    owner.querySelector('b').dataset.programOwner = '';
                    modules.append(
                        createExamElement('small', '', 'Modulos'),
                        createExamElement('b', '', String(data.modules || 0))
                    );
                    header.append(icon, status);
                    meta.append(owner, modules);
                    selectButton.append(header, title, description, meta);
                    editButton.type = 'button';
                    editButton.dataset.editProgram = '';
                    editButton.title = 'Editar programa';
                    editButton.setAttribute('aria-label', 'Editar ' + data.title);
                    editButton.appendChild(createExamElement(
                        'span',
                        'training-program-edit-glyph',
                        String.fromCharCode(9998)
                    ));
                    editButton.firstElementChild.setAttribute('aria-hidden', 'true');
                    card.append(selectButton, editButton);
                    programCarouselTrack.appendChild(card);
                    applyProgramData(card, data);
                    requestAnimationFrame(updateProgramCarouselNavigation);

                    return card;
                }

                function applyProgramData(card, data) {
                    if (!card || !data) {
                        return;
                    }

                    card.querySelector('[data-program-title]').textContent = data.title;
                    card.querySelector('[data-program-description]').textContent = data.description;
                    card.querySelector('[data-program-owner]').textContent = data.owner;

                    const status = card.querySelector('[data-program-status]');
                    const statusTone = {
                        Activo: 'active',
                        Borrador: 'draft',
                        Inactivo: 'inactive',
                    }[data.status] || 'draft';

                    status.textContent = data.status;
                    status.classList.remove('is-active', 'is-draft', 'is-inactive');
                    status.classList.add('is-' + statusTone);

                    const editButton = card.querySelector('[data-edit-program]');

                    if (editButton) {
                        editButton.setAttribute('aria-label', 'Editar ' + data.title);
                    }

                    if (programSelector) {
                        const option = Array.from(programSelector.options).find(function(item) {
                            return item.value === card.dataset.programId;
                        });

                        if (option) {
                            option.textContent = data.title;
                        }

                        if (programSelector.value === card.dataset.programId) {
                            updateSelectedProgramName(data.title);
                        }
                    }
                }

                function openProgramEditor(card) {
                    if (!card || !programModal || !programForm) {
                        return;
                    }

                    programForm.elements.program_id.value = card.dataset.programId;
                    programForm.elements.title.value = card.querySelector('[data-program-title]').textContent.trim();
                    programForm.elements.description.value = card.querySelector('[data-program-description]').textContent.trim();
                    programForm.elements.owner.value = card.querySelector('[data-program-owner]').textContent.trim();
                    programForm.elements.status.value = card.querySelector('[data-program-status]').textContent.trim();

                    if (programModalTitle) {
                        programModalTitle.textContent = 'Editar programa';
                    }

                    if (programSubmitLabel) {
                        programSubmitLabel.textContent = 'Guardar cambios';
                    }

                    programModal.showModal();
                    programForm.elements.title.focus();
                }

                function openNewProgramModal() {
                    if (!programModal || !programForm) {
                        return;
                    }

                    programForm.reset();
                    programForm.elements.program_id.value = '';
                    programForm.elements.status.value = 'Borrador';

                    if (programModalTitle) {
                        programModalTitle.textContent = 'Nuevo programa';
                    }

                    if (programSubmitLabel) {
                        programSubmitLabel.textContent = 'Crear programa';
                    }

                    programModal.showModal();
                    programForm.elements.title.focus();
                }

                function closeProgramModal() {
                    if (programModal && programModal.open) {
                        programModal.close();
                    }
                }

                root.querySelectorAll('[data-add-training]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        openTrainingAssignment(button);
                    });
                });

                root.querySelector('[data-open-new-training]')?.addEventListener('click', openNewTrainingAssignment);

                assignmentStudentSelect?.addEventListener('change', function() {
                    const selectedOption = assignmentStudentSelect.options[assignmentStudentSelect.selectedIndex];
                    const studentId = selectedOption?.value || '';
                    const studentName = selectedOption?.dataset.studentName || '';

                    assignmentTargetRow = studentId
                        ? Array.from(root.querySelectorAll('[data-student-row]')).find(function(row) {
                            return row.dataset.studentId === studentId;
                        }) || {
                            dataset: {
                                studentId: studentId,
                                studentName: studentName,
                            },
                        }
                        : null;

                    if (assignmentStudent) {
                        assignmentStudent.textContent = studentName;
                    }

                    updateAssignmentSelection();
                });

                assignmentStartDate?.addEventListener('change', updateAssignmentSelection);

                root.querySelectorAll('[data-close-training-assignment]').forEach(function(button) {
                    button.addEventListener('click', closeTrainingAssignment);
                });

                assignmentPrograms.forEach(function(program) {
                    const programCheckbox = program.querySelector('[data-assignment-program-checkbox]');
                    const moduleCheckboxes = program.querySelectorAll('[data-assignment-module-checkbox]');

                    if (programCheckbox) {
                        programCheckbox.addEventListener('change', function() {
                            moduleCheckboxes.forEach(function(checkbox) {
                                checkbox.checked = programCheckbox.checked;
                            });

                            programCheckbox.indeterminate = false;
                            updateAssignmentSelection();
                        });
                    }

                    moduleCheckboxes.forEach(function(checkbox) {
                        checkbox.addEventListener('change', function() {
                            syncProgramCheckbox(program);
                            updateAssignmentSelection();
                        });
                    });
                });

                if (assignmentSelectAll) {
                    assignmentSelectAll.addEventListener('change', function() {
                        assignmentPrograms.forEach(function(program) {
                            const programCheckbox = program.querySelector('[data-assignment-program-checkbox]');

                            program.querySelectorAll('[data-assignment-module-checkbox]').forEach(function(checkbox) {
                                checkbox.checked = assignmentSelectAll.checked;
                            });

                            if (programCheckbox) {
                                programCheckbox.checked = assignmentSelectAll.checked;
                                programCheckbox.indeterminate = false;
                            }
                        });

                        updateAssignmentSelection();
                    });
                }

                if (trainingAssignmentModal) {
                    trainingAssignmentModal.addEventListener('click', function(event) {
                        if (event.target === trainingAssignmentModal) {
                            closeTrainingAssignment();
                        }
                    });

                    trainingAssignmentModal.addEventListener('close', function() {
                        assignmentTargetRow = null;
                    });
                }

                if (trainingAssignmentForm) {
                    trainingAssignmentForm.addEventListener('submit', function(event) {
                        event.preventDefault();

                        if (!assignmentTargetRow) {
                            return;
                        }

                        const selectedAssignments = collectAssignments();

                        if (!selectedAssignments.length) {
                            return;
                        }

                        const studentName = assignmentTargetRow.dataset.studentName;
                        const storedAssignments = readStoredAssignments();
                        const mergedAssignments = mergeAssignments(
                            storedAssignments[studentName] || [],
                            selectedAssignments
                        );

                        storedAssignments[studentName] = mergedAssignments;
                        persistAssignments(storedAssignments);
                        hydrateLearnerSelectorFromAssignments();

                        if (typeof assignmentTargetRow.querySelector === 'function') {
                            renderAssignmentsForRow(assignmentTargetRow, mergedAssignments);
                        }

                        closeTrainingAssignment();
                    });
                }

                employmentStatusControls.forEach(function(control) {
                    const select = control.querySelector('[data-student-status]');

                    if (!select) {
                        return;
                    }

                    select.addEventListener('change', function() {
                        const row = control.closest('[data-student-row]');

                        setEmploymentStatus(control, select.value);

                        if (!row) {
                            return;
                        }

                        const storedStatuses = readStoredEmploymentStatuses();
                        storedStatuses[row.dataset.studentName] = select.value;
                        persistEmploymentStatuses(storedStatuses);
                    });
                });

                applyStoredAssignments();
                applyStoredEmploymentStatuses();

                function normaliseLearnerIdentity(value) {
                    return String(value || '')
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-|-$/g, '');
                }

                function learnerProfileId(name) {
                    return 'assigned-' + (normaliseLearnerIdentity(name) || 'learner');
                }

                // Existing training records in this browser are indexed by the displayed name.
                document.addEventListener('personnel-general-updated', function(event) {
                    const previousName = event.detail.previous_name;
                    const name = event.detail.name;
                    if (!previousName || previousName === name) return;
                    const hasNamesake = Array.from(root.querySelectorAll('[data-student-row]'))
                        .filter(row => row.dataset.studentName === previousName).length > 1;

                    const assignments = readStoredAssignments();
                    if (Array.isArray(assignments[previousName])) {
                        assignments[name] = mergeAssignments(assignments[name] || [], assignments[previousName]);
                        if (!hasNamesake) delete assignments[previousName];
                        persistAssignments(assignments);
                    }
                    const statuses = readStoredEmploymentStatuses();
                    if (statuses[previousName]) {
                        statuses[name] = statuses[name] || statuses[previousName];
                        if (!hasNamesake) delete statuses[previousName];
                        persistEmploymentStatuses(statuses);
                    }
                    try {
                        const oldId = learnerProfileId(previousName);
                        const newId = learnerProfileId(name);
                        const oldState = window.localStorage.getItem(learnerStateStorageKey + '.' + oldId);
                        if (oldState && !window.localStorage.getItem(learnerStateStorageKey + '.' + newId)) {
                            window.localStorage.setItem(learnerStateStorageKey + '.' + newId, oldState);
                        }
                        if (window.localStorage.getItem(learnerSelectionStorageKey) === oldId) {
                            window.localStorage.setItem(learnerSelectionStorageKey, newId);
                        }
                    } catch (error) {
                        // A storage restriction must not interrupt a completed personnel update.
                    }
                });

                function hydrateLearnerSelectorFromAssignments() {
                    if (!learnerSelector) {
                        return;
                    }

                    const selectedValue = learnerSelector.value;
                    const storedAssignments = readStoredAssignments();

                    Object.entries(storedAssignments).forEach(function(entry) {
                        const studentName = entry[0];
                        const assignments = entry[1];

                        if (!Array.isArray(assignments) || !assignments.length) {
                            return;
                        }

                        const identity = normaliseLearnerIdentity(studentName);
                        const alreadyListed = Array.from(learnerSelector.options).some(function(option) {
                            return normaliseLearnerIdentity(option.dataset.learnerName) === identity;
                        });

                        if (alreadyListed) {
                            return;
                        }

                        const firstAssignment = assignments[0] || {};
                        const option = document.createElement('option');

                        option.value = learnerProfileId(studentName);
                        option.dataset.learnerName = studentName;
                        option.dataset.programId = firstAssignment.programId || '';
                        option.dataset.programName = firstAssignment.programName || 'Capacitacion asignada';
                        option.dataset.progress = String(firstAssignment.progress || 0);
                        option.textContent = studentName + ' - ' + option.dataset.programName;
                        learnerSelector.append(option);
                    });

                    Array.from(learnerSelector.options)
                        .sort(function(left, right) {
                            return (left.dataset.learnerName || '').localeCompare(
                                right.dataset.learnerName || '',
                                'es',
                                { sensitivity: 'base' }
                            );
                        })
                        .forEach(function(option) {
                            learnerSelector.append(option);
                        });

                    if (selectedValue && Array.from(learnerSelector.options).some(function(option) {
                        return option.value === selectedValue;
                    })) {
                        learnerSelector.value = selectedValue;
                    }

                    if (openLearnerPanelButton) {
                        openLearnerPanelButton.disabled = learnerSelector.options.length === 0;
                    }
                }

                function restoreLearnerSelection() {
                    if (!learnerSelector) {
                        return;
                    }

                    try {
                        const storedSelection = window.localStorage.getItem(learnerSelectionStorageKey);
                        const optionExists = Array.from(learnerSelector.options).some(function(option) {
                            return option.value === storedSelection;
                        });

                        if (optionExists) {
                            learnerSelector.value = storedSelection;
                        }
                    } catch (error) {
                        // Keep the first available learner selected when storage is unavailable.
                    }
                }

                function readSelectedLearnerProfile() {
                    const selectedOption = learnerSelector?.options[learnerSelector.selectedIndex];

                    if (!selectedOption) {
                        return Object.assign({}, defaultLearnerProfile);
                    }

                    return {
                        id: selectedOption.value,
                        name: selectedOption.dataset.learnerName || selectedOption.textContent.trim(),
                        programId: selectedOption.dataset.programId || '',
                        programName: selectedOption.dataset.programName || '',
                        progress: Math.min(100, Math.max(0, Number(selectedOption.dataset.progress) || 0)),
                    };
                }

                function learnerStateKey() {
                    return learnerStateStorageKey + '.' + activeLearnerId;
                }

                function readLearnerState() {
                    const defaultState = createDefaultLearnerState();

                    try {
                        const storedState = JSON.parse(window.localStorage.getItem(learnerStateKey()) || '{}');

                        if (!storedState || typeof storedState !== 'object' || Array.isArray(storedState)) {
                            return defaultState;
                        }

                        return {
                            completedVideos: Array.isArray(storedState.completedVideos)
                                ? Array.from(new Set(storedState.completedVideos.map(String)))
                                : defaultState.completedVideos,
                            videoProgress: Object.assign(
                                {},
                                defaultState.videoProgress,
                                storedState.videoProgress && typeof storedState.videoProgress === 'object' && !Array.isArray(storedState.videoProgress)
                                    ? storedState.videoProgress
                                    : {}
                            ),
                            tasks: Object.assign({}, defaultState.tasks, storedState.tasks || {}),
                            exams: Object.assign({}, defaultState.exams, storedState.exams || {}),
                        };
                    } catch (error) {
                        return defaultState;
                    }
                }

                function createDefaultLearnerState() {
                    const completedVideos = [];
                    const videoProgress = {};
                    const tasks = learnerCurriculum.reduce(function(records, module) {
                        records[String(module.taskId)] = { status: 'locked' };
                        return records;
                    }, {});
                    const exams = learnerCurriculum.reduce(function(records, module) {
                        records[module.examId] = { status: 'locked', attempts: 0, answers: [] };
                        return records;
                    }, {});
                    const totalSteps = learnerCurriculum.reduce(function(total, module) {
                        return total + getModuleVideos(module).length + 2;
                    }, 0);
                    const targetProgress = Math.min(100, Math.max(0, Number(activeLearnerProfile?.progress) || 0));
                    let remainingSteps = Math.round((totalSteps * targetProgress) / 100);

                    learnerCurriculum.forEach(function(module) {
                        if (remainingSteps <= 0) {
                            return;
                        }

                        const moduleVideos = getModuleVideos(module);

                        moduleVideos.forEach(function(video) {
                            if (remainingSteps <= 0) {
                                return;
                            }

                            completedVideos.push(video.id);
                            videoProgress[video.id] = 100;
                            remainingSteps -= 1;
                        });

                        if (remainingSteps <= 0 || moduleVideos.some(function(video) {
                            return !completedVideos.includes(video.id);
                        })) {
                            return;
                        }

                        tasks[String(module.taskId)] = {
                            status: 'approved',
                            response: 'Actividad completada y validada por el responsable de capacitacion.',
                            fileName: 'evidencia_capacitacion.pdf',
                        };
                        remainingSteps -= 1;

                        if (remainingSteps <= 0) {
                            return;
                        }

                        const exam = getLearnerExam(module);

                        exams[module.examId] = {
                            status: 'passed',
                            score: 88,
                            correct: exam.items.length,
                            total: exam.items.length,
                            attempts: 1,
                            answers: exam.items.map(function(question) {
                                return Array.isArray(question.correct)
                                    ? question.correct.slice()
                                    : question.correct;
                            }),
                        };
                        remainingSteps -= 1;
                    });

                    return {
                        completedVideos: completedVideos,
                        videoProgress: videoProgress,
                        tasks: tasks,
                        exams: exams,
                    };
                }

                function persistLearnerState() {
                    try {
                        window.localStorage.setItem(learnerStateKey(), JSON.stringify(learnerState));
                    } catch (error) {
                        // Keep the learner workflow available for the current session.
                    }
                }

                function getStoredVideoProgress(videoId) {
                    if (!videoId) {
                        return 0;
                    }

                    if ((learnerState.completedVideos || []).includes(videoId)) {
                        return 100;
                    }

                    return Math.min(100, Math.max(0, Number(learnerState.videoProgress?.[videoId]) || 0));
                }

                function persistCurrentVideoProgress() {
                    const videoId = root.querySelector('[data-training-video-play]')?.dataset.videoId;

                    if (!videoId || !learnerState) {
                        return;
                    }

                    learnerState.videoProgress = learnerState.videoProgress || {};
                    learnerState.videoProgress[videoId] = Math.min(
                        100,
                        Math.max(0, Math.round(learnerPlaybackProgress))
                    );
                    persistLearnerState();
                }

                function closeLearnerVideoDialog() {
                    persistCurrentVideoProgress();
                    stopLearnerPlayback(false);
                    updateLearnerPlaybackControls();
                    closeLearnerDialog(learnerVideoDialog);
                }

                function openLearnerVideoDialog(autoPlay) {
                    if (currentLearnerObligation?.type !== 'content' || !learnerVideoDialog) {
                        return;
                    }

                    learnerPlaybackProgress = getStoredVideoProgress(currentLearnerObligation.video.id);
                    updateLearnerPlaybackControls();
                    openLearnerDialog(learnerVideoDialog);

                    window.requestAnimationFrame(function() {
                        const videoButton = root.querySelector('[data-training-video-play]');

                        videoButton?.focus();

                        if (autoPlay && videoButton && !learnerPlaybackTimer && learnerPlaybackProgress < 100) {
                            videoButton.click();
                        }
                    });
                }

                function renderActiveLearnerIdentity() {
                    activeLearnerNameElements.forEach(function(element) {
                        element.textContent = activeLearnerProfile?.name || 'Alumno';
                    });
                }

                function openSelectedLearnerPanel() {
                    if (!learnerSelector?.value) {
                        return;
                    }

                    closeLearnerVideoDialog();
                    stopLearnerPlayback(true);
                    closeLearnerDialog(learnerTaskDialog);
                    closeLearnerDialog(learnerExamDialog);
                    closeLearnerDialog(resultReviewDialog);
                    const learnerVideoButton = root.querySelector('[data-training-video-play]');

                    if (learnerVideoButton) {
                        learnerVideoButton.dataset.videoId = '';
                    }

                    activeLearnerTaskRow = null;
                    activeLearnerExamModule = null;
                    activeLearnerId = learnerSelector.value;
                    activeLearnerProfile = readSelectedLearnerProfile();
                    learnerState = readLearnerState();

                    try {
                        window.localStorage.setItem(learnerSelectionStorageKey, activeLearnerId);
                    } catch (error) {
                        // Keep the selected learner for the current session.
                    }

                    renderActiveLearnerIdentity();
                    renderLearnerExperience();
                    activateLearnerTab('home', false);
                }

                function createLearnerElement(tagName, className, textContent) {
                    const element = document.createElement(tagName);

                    if (className) {
                        element.className = className;
                    }

                    if (typeof textContent === 'string') {
                        element.textContent = textContent;
                    }

                    return element;
                }

                function getModuleVideos(module) {
                    const chapters = Array.isArray(module?.chapters) ? module.chapters : [];

                    return chapters.reduce(function(videos, chapter, chapterIndex) {
                        const chapterVideos = Array.isArray(chapter.videos) ? chapter.videos : [];

                        chapterVideos.forEach(function(video, videoIndex) {
                            videos.push(Object.assign({}, video, {
                                chapterTitle: chapter.title,
                                chapterNumber: chapterIndex + 1,
                                chapterTotal: chapters.length,
                                videoNumber: videoIndex + 1,
                            }));
                        });

                        return videos;
                    }, []);
                }

                function getLearnerExam(module) {
                    const sourceExam = examRecords[module.examId]
                        || examDefaults[module.examId]
                        || learnerExamFallbacks[module.examId]
                        || {};

                    return normaliseExam(Object.assign({}, sourceExam, {
                        id: module.examId,
                        module: module.title,
                        minimum: sourceExam.minimum ?? module.minimum,
                    }));
                }

                function isTaskCompleted(status) {
                    return status === 'review' || status === 'approved';
                }

                function areModuleVideosComplete(module) {
                    const completedVideos = new Set(learnerState.completedVideos || []);

                    return getModuleVideos(module).every(function(video) {
                        return completedVideos.has(video.id);
                    });
                }

                function isModulePassed(module) {
                    const task = learnerState.tasks?.[String(module.taskId)] || {};
                    const exam = learnerState.exams?.[module.examId] || {};

                    return areModuleVideosComplete(module)
                        && isTaskCompleted(task.status)
                        && exam.status === 'passed';
                }

                function synchronizeLearnerState() {
                    learnerState.completedVideos = Array.from(new Set(learnerState.completedVideos || []));
                    learnerState.videoProgress = learnerState.videoProgress && typeof learnerState.videoProgress === 'object'
                        ? learnerState.videoProgress
                        : {};
                    learnerState.tasks = learnerState.tasks || {};
                    learnerState.exams = learnerState.exams || {};

                    Object.keys(learnerState.videoProgress).forEach(function(videoId) {
                        learnerState.videoProgress[videoId] = Math.min(
                            100,
                            Math.max(0, Number(learnerState.videoProgress[videoId]) || 0)
                        );
                    });
                    learnerState.completedVideos.forEach(function(videoId) {
                        learnerState.videoProgress[videoId] = 100;
                    });

                    let previousModulePassed = true;

                    learnerCurriculum.forEach(function(module) {
                        const taskId = String(module.taskId);
                        const task = learnerState.tasks[taskId] || { status: 'locked' };
                        const exam = learnerState.exams[module.examId] || { status: 'locked', attempts: 0, answers: [] };
                        const videosComplete = areModuleVideosComplete(module);

                        learnerState.tasks[taskId] = task;
                        learnerState.exams[module.examId] = exam;

                        if (!previousModulePassed) {
                            task.status = 'locked';
                            exam.status = 'locked';
                            previousModulePassed = false;
                            return;
                        }

                        if (!videosComplete) {
                            if (!isTaskCompleted(task.status)) {
                                task.status = 'locked';
                            }

                            if (exam.status !== 'passed') {
                                exam.status = 'locked';
                            }
                        } else if (!isTaskCompleted(task.status)) {
                            if (task.status !== 'corrections') {
                                task.status = 'pending';
                            }

                            if (exam.status !== 'passed') {
                                exam.status = 'locked';
                            }
                        } else if (!['available', 'failed', 'passed'].includes(exam.status)) {
                            exam.status = 'available';
                        }

                        previousModulePassed = isModulePassed(module);
                    });
                }

                function getModuleProgress(module) {
                    const videos = getModuleVideos(module);
                    const completedVideos = new Set(learnerState.completedVideos || []);
                    const completedVideoCount = videos.filter(function(video) {
                        return completedVideos.has(video.id);
                    }).length;
                    const task = learnerState.tasks?.[String(module.taskId)] || {};
                    const exam = learnerState.exams?.[module.examId] || {};
                    const totalSteps = videos.length + 2;
                    const completedSteps = completedVideoCount
                        + (isTaskCompleted(task.status) ? 1 : 0)
                        + (exam.status === 'passed' ? 1 : 0);

                    return totalSteps ? Math.round((completedSteps / totalSteps) * 100) : 0;
                }

                function getOverallLearnerProgress() {
                    let totalSteps = 0;
                    let completedSteps = 0;
                    const completedVideos = new Set(learnerState.completedVideos || []);

                    learnerCurriculum.forEach(function(module) {
                        const videos = getModuleVideos(module);
                        const task = learnerState.tasks?.[String(module.taskId)] || {};
                        const exam = learnerState.exams?.[module.examId] || {};

                        totalSteps += videos.length + 2;
                        completedSteps += videos.filter(function(video) {
                            return completedVideos.has(video.id);
                        }).length;
                        completedSteps += isTaskCompleted(task.status) ? 1 : 0;
                        completedSteps += exam.status === 'passed' ? 1 : 0;
                    });

                    return totalSteps ? Math.round((completedSteps / totalSteps) * 100) : 0;
                }

                function getNextLearnerObligation() {
                    const completedVideos = new Set(learnerState.completedVideos || []);

                    for (const module of learnerCurriculum) {
                        if (isModulePassed(module)) {
                            continue;
                        }

                        const nextVideo = getModuleVideos(module).find(function(video) {
                            return !completedVideos.has(video.id);
                        });

                        if (nextVideo) {
                            return { type: 'content', module: module, video: nextVideo };
                        }

                        const task = learnerState.tasks?.[String(module.taskId)] || {};

                        if (!isTaskCompleted(task.status)) {
                            return { type: 'task', module: module, taskId: String(module.taskId) };
                        }

                        return { type: 'exam', module: module, exam: getLearnerExam(module) };
                    }

                    return { type: 'certificate', module: learnerCurriculum[learnerCurriculum.length - 1] || null };
                }

                function getObligationActionLabel(obligation) {
                    return {
                        content: 'Continuar video',
                        task: 'Realizar tarea',
                        exam: learnerState.exams?.[obligation?.module?.examId]?.status === 'failed'
                            ? 'Reintentar examen'
                            : 'Presentar examen',
                        certificate: 'Ver certificado',
                    }[obligation?.type] || 'Continuar';
                }

                function updateLearnerFlow(obligation) {
                    const stageOrder = ['content', 'task', 'exam', 'next'];
                    const activeStage = obligation.type === 'certificate' ? 'next' : obligation.type;
                    const activeIndex = stageOrder.indexOf(activeStage);
                    const moduleNumber = obligation.module?.number || learnerCurriculum.length;
                    const stageDetails = {
                        content: 'Modulo ' + moduleNumber,
                        task: activeIndex > 1 ? 'Tarea entregada' : 'Entrega obligatoria',
                        exam: activeIndex > 2 ? 'Examen aprobado' : 'Minimo 80%',
                        next: obligation.type === 'certificate' ? 'Programa terminado' : 'Al aprobar el modulo',
                    };

                    root.querySelectorAll('[data-obligation-stage]').forEach(function(stage) {
                        const stageIndex = stageOrder.indexOf(stage.dataset.obligationStage);

                        stage.classList.toggle('is-complete', stageIndex < activeIndex);
                        stage.classList.toggle('is-active', stageIndex === activeIndex);
                        stage.classList.toggle('is-locked', stageIndex > activeIndex);

                        const detail = stage.querySelector('small');
                        if (detail) {
                            detail.textContent = stageDetails[stage.dataset.obligationStage];
                        }
                    });
                }

                function stopLearnerPlayback(resetProgress) {
                    if (learnerPlaybackTimer) {
                        window.clearInterval(learnerPlaybackTimer);
                        learnerPlaybackTimer = null;
                    }

                    if (resetProgress) {
                        learnerPlaybackProgress = 0;
                    }
                }

                function updateLearnerPlaybackControls() {
                    const videoButton = root.querySelector('[data-training-video-play]');
                    const playbackStatus = root.querySelector('[data-video-playback-status]');
                    const playbackFill = root.querySelector('[data-video-playback-fill]');
                    const completeButton = root.querySelector('[data-complete-training-video]');
                    const icon = videoButton?.querySelector('i');
                    const isContentStep = currentLearnerObligation?.type === 'content';
                    const roundedProgress = Math.min(100, Math.round(learnerPlaybackProgress));
                    const isComplete = roundedProgress >= 100;
                    const isPlaying = Boolean(learnerPlaybackTimer);

                    if (!videoButton || !playbackStatus || !playbackFill || !completeButton || !icon) {
                        return;
                    }

                    videoButton.classList.toggle('is-playing', isPlaying);
                    videoButton.disabled = !isContentStep || isComplete;
                    icon.className = isComplete
                        ? 'fa-solid fa-check'
                        : (isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play');
                    playbackFill.style.width = roundedProgress + '%';

                    if (!isContentStep) {
                        playbackStatus.textContent = 'Contenido del modulo completado';
                        completeButton.textContent = 'Contenido completado';
                        completeButton.disabled = true;
                    } else if (isComplete) {
                        playbackStatus.textContent = 'Video visto al 100%';
                        completeButton.textContent = 'Finalizar video y continuar';
                        completeButton.disabled = false;
                    } else if (isPlaying) {
                        playbackStatus.textContent = 'Reproduciendo ' + roundedProgress + '%';
                        completeButton.textContent = 'Termina el video para continuar';
                        completeButton.disabled = true;
                    } else if (roundedProgress > 0) {
                        playbackStatus.textContent = 'Pausado en ' + roundedProgress + '%';
                        completeButton.textContent = 'Termina el video para continuar';
                        completeButton.disabled = true;
                    } else {
                        playbackStatus.textContent = 'Listo para reproducir';
                        completeButton.textContent = 'Termina el video para continuar';
                        completeButton.disabled = true;
                    }

                    videoButton.setAttribute('aria-label', isPlaying
                        ? 'Pausar video de capacitacion'
                        : 'Reproducir video de capacitacion');
                }

                function renderLearnerContentOutline(module, currentVideoId) {
                    const outline = root.querySelector('[data-content-outline]');
                    const completedVideos = new Set(learnerState.completedVideos || []);

                    if (!outline || !module) {
                        return;
                    }

                    outline.replaceChildren();

                    (module.chapters || []).forEach(function(chapter, chapterIndex) {
                        const chapterElement = createLearnerElement('section', 'training-content-chapter');
                        const chapterHeader = createLearnerElement('header');
                        const chapterTitle = createLearnerElement('strong', '', 'Capitulo ' + (chapterIndex + 1) + ' · ' + chapter.title);
                        const chapterVideos = Array.isArray(chapter.videos) ? chapter.videos : [];
                        const completedCount = chapterVideos.filter(function(video) {
                            return completedVideos.has(video.id);
                        }).length;
                        const chapterCount = createLearnerElement('small', '', completedCount + ' de ' + chapterVideos.length + ' vistos');

                        chapterHeader.append(chapterTitle, chapterCount);
                        chapterElement.appendChild(chapterHeader);

                        chapterVideos.forEach(function(video) {
                            const isComplete = completedVideos.has(video.id);
                            const isCurrent = video.id === currentVideoId;
                            const videoRow = createLearnerElement('button', 'training-content-video');
                            const marker = createLearnerElement('span');
                            const markerIcon = createLearnerElement('i', isComplete
                                ? 'fa-solid fa-check'
                                : (isCurrent ? 'fa-solid fa-play' : 'fa-solid fa-lock'));
                            const copy = createLearnerElement('span');
                            const title = createLearnerElement('strong', '', video.title);
                            const duration = createLearnerElement('small', '', video.duration);
                            const status = createLearnerElement('em', '', isComplete ? 'Visto' : (isCurrent ? 'Continuar' : 'Pendiente'));

                            videoRow.type = 'button';
                            videoRow.classList.add(isComplete ? 'is-complete' : (isCurrent ? 'is-current' : 'is-locked'));
                            videoRow.disabled = !isCurrent;
                            videoRow.dataset.outlineVideo = video.id;
                            markerIcon.setAttribute('aria-hidden', 'true');
                            marker.appendChild(markerIcon);
                            copy.append(title, duration);
                            videoRow.append(marker, copy, status);
                            chapterElement.appendChild(videoRow);
                        });

                        outline.appendChild(chapterElement);
                    });
                }

                function renderLearnerHome(obligation) {
                    const eyebrow = root.querySelector('[data-next-obligation-eyebrow]');
                    const summary = root.querySelector('[data-next-obligation-summary]');
                    const action = root.querySelector('[data-next-obligation-action]');
                    const actionLabel = root.querySelector('[data-next-obligation-label]');
                    const actionIcon = action?.querySelector('i');
                    const module = obligation.module || learnerCurriculum[learnerCurriculum.length - 1];
                    const moduleVideos = module ? getModuleVideos(module) : [];
                    const video = obligation.type === 'content' ? obligation.video : moduleVideos[moduleVideos.length - 1];
                    const videoButton = root.querySelector('[data-training-video-play]');
                    const progress = module ? getModuleProgress(module) : 100;
                    const typeCopy = {
                        content: {
                            eyebrow: 'Modulo ' + module.number + ' · Contenido obligatorio',
                            summary: 'Mira "' + video.title + '" para continuar el recorrido.',
                            icon: 'fa-solid fa-play',
                        },
                        task: {
                            eyebrow: 'Modulo ' + module.number + ' · Tarea obligatoria',
                            summary: 'Terminaste todos los videos. Entrega la tarea del modulo para habilitar el examen.',
                            icon: 'fa-regular fa-clipboard',
                        },
                        exam: {
                            eyebrow: 'Modulo ' + module.number + ' · Examen obligatorio',
                            summary: 'La tarea esta entregada. Aprueba el examen para desbloquear el siguiente modulo.',
                            icon: 'fa-regular fa-file-lines',
                        },
                        certificate: {
                            eyebrow: 'Programa completado',
                            summary: 'Aprobaste todos los modulos. Tu certificado ya esta disponible.',
                            icon: 'fa-solid fa-award',
                        },
                    }[obligation.type];

                    if (!module || !video || !typeCopy) {
                        return;
                    }

                    eyebrow.textContent = typeCopy.eyebrow;
                    summary.textContent = typeCopy.summary;
                    actionLabel.textContent = getObligationActionLabel(obligation);
                    actionIcon.className = typeCopy.icon;

                    root.querySelector('[data-current-module-label]').textContent = 'Modulo ' + module.number;
                    root.querySelector('[data-current-module-title]').textContent = module.title;
                    root.querySelector('[data-current-module-progress]').textContent = progress + '% del modulo';
                    root.querySelector('[data-current-video-title]').textContent = video.title;
                    root.querySelector('[data-current-chapter-label]').textContent = 'Capitulo ' + video.chapterNumber + ' de ' + video.chapterTotal;
                    root.querySelector('[data-current-chapter-title]').textContent = video.chapterTitle;
                    root.querySelector('[data-current-video-description]').textContent = video.description;

                    if (videoButton.dataset.videoId !== video.id) {
                        persistCurrentVideoProgress();
                        stopLearnerPlayback(false);
                        videoButton.dataset.videoId = video.id;
                        learnerPlaybackProgress = getStoredVideoProgress(video.id);
                    }

                    if (obligation.type !== 'content') {
                        stopLearnerPlayback(false);
                        learnerPlaybackProgress = 100;
                        closeLearnerVideoDialog();
                    }

                    renderLearnerContentOutline(module, obligation.type === 'content' ? video.id : null);
                    updateLearnerPlaybackControls();
                }

                function renderLearnerModules(obligation) {
                    learnerCurriculum.forEach(function(module, moduleIndex) {
                        const moduleElement = root.querySelector('[data-learner-module="' + module.number + '"]');
                        const pathStep = root.querySelector('[data-learning-path-step="' + module.number + '"]');
                        const previousModule = learnerCurriculum[moduleIndex - 1];
                        const isPassed = isModulePassed(module);
                        const isUnlocked = moduleIndex === 0 || isModulePassed(previousModule);
                        const status = isPassed ? 'approved' : (isUnlocked ? 'current' : 'locked');
                        const statusLabel = status === 'approved' ? 'Aprobado' : (status === 'current' ? 'En curso' : 'Bloqueado');

                        if (!moduleElement || !pathStep) {
                            return;
                        }

                        pathStep.className = 'training-learning-path-step is-' + status;
                        pathStep.querySelector('strong').textContent = statusLabel;

                        moduleElement.className = 'training-learner-module is-' + status;
                        moduleElement.dataset.moduleStatus = status;

                        const marker = moduleElement.querySelector('.training-learner-module-marker');
                        const detail = moduleElement.querySelector('[data-module-detail]');
                        const actions = moduleElement.querySelector('.training-learner-module-actions');

                        if (status === 'approved') {
                            const markerIcon = createLearnerElement('i', 'fa-solid fa-check');
                            const meta = createLearnerElement('div', 'training-module-meta');
                            const chapterMeta = createLearnerElement('span');
                            const taskMeta = createLearnerElement('span');
                            const examMeta = createLearnerElement('span');
                            const chapterIcon = createLearnerElement('i', 'fa-regular fa-book-open');
                            const taskIcon = createLearnerElement('i', 'fa-regular fa-clipboard');
                            const examIcon = createLearnerElement('i', 'fa-regular fa-chart-pie');
                            const examScore = learnerState.exams?.[module.examId]?.score ?? 0;
                            const examScoreLabel = createLearnerElement('strong', '', examScore + '%');
                            const resultButton = createLearnerElement('button', 'training-secondary-button', 'Ver resultado');

                            markerIcon.setAttribute('aria-hidden', 'true');
                            chapterIcon.setAttribute('aria-hidden', 'true');
                            taskIcon.setAttribute('aria-hidden', 'true');
                            examIcon.setAttribute('aria-hidden', 'true');
                            marker.replaceChildren(markerIcon);
                            chapterMeta.append(chapterIcon, document.createTextNode(module.chapters.length + ' capitulos'));
                            taskMeta.append(taskIcon, document.createTextNode('Tarea entregada'));
                            examMeta.append(examIcon, document.createTextNode('Examen '), examScoreLabel);
                            meta.append(chapterMeta, taskMeta, examMeta);
                            detail.replaceChildren(meta);

                            resultButton.type = 'button';
                            resultButton.dataset.learnerGoTo = 'results';
                            actions.replaceChildren(createModuleStatePill('approved'), resultButton);
                            return;
                        }

                        if (status === 'current') {
                            const markerNumber = createLearnerElement('span', '', String(module.number));
                            const moduleProgress = createLearnerElement('div', 'training-module-progress');
                            const percentage = createLearnerElement('strong', '', getModuleProgress(module) + '%');
                            const track = createLearnerElement('div', 'training-progress-track');
                            const fill = createLearnerElement('span');
                            const actionButton = createLearnerElement('button', 'training-primary-button', getObligationActionLabel(obligation));

                            marker.replaceChildren(markerNumber);
                            track.setAttribute('aria-label', 'Progreso del modulo: ' + getModuleProgress(module) + '%');
                            fill.style.width = getModuleProgress(module) + '%';
                            track.appendChild(fill);
                            moduleProgress.append(percentage, track);
                            detail.replaceChildren(moduleProgress);
                            actionButton.type = 'button';
                            actionButton.dataset.modulePrimaryAction = '';
                            actions.replaceChildren(createModuleStatePill('current'), actionButton);
                            return;
                        }

                        const lockIcon = createLearnerElement('i', 'fa-solid fa-lock');
                        const requirement = createLearnerElement('p');
                        const requiredModuleNumber = Math.max(1, module.number - 1);

                        lockIcon.setAttribute('aria-hidden', 'true');
                        marker.replaceChildren(lockIcon);
                        requirement.append(
                            createLearnerElement('strong', '', 'Bloqueado'),
                            document.createTextNode(' · Debes aprobar el modulo ' + requiredModuleNumber + ' para desbloquear este contenido.')
                        );
                        detail.replaceChildren(requirement);
                        actions.replaceChildren(createModuleStatePill('locked'));
                    });
                }

                function getLatestLearnerResult() {
                    return learnerCurriculum.reduce(function(latest, module) {
                        const examState = learnerState.exams?.[module.examId] || {};

                        return ['passed', 'failed'].includes(examState.status)
                            ? { module: module, state: examState, exam: getLearnerExam(module) }
                            : latest;
                    }, null);
                }

                function renderLearnerResultHistory() {
                    const history = root.querySelector('[data-result-history]');

                    if (!history) {
                        return;
                    }

                    history.replaceChildren();

                    learnerCurriculum.forEach(function(module, moduleIndex) {
                        const examState = learnerState.exams?.[module.examId] || {};
                        const previousModule = learnerCurriculum[moduleIndex - 1];
                        const isUnlocked = moduleIndex === 0 || isModulePassed(previousModule);
                        const status = examState.status === 'passed'
                            ? 'approved'
                            : (examState.status === 'failed' ? 'failed' : (isUnlocked ? 'current' : 'locked'));
                        const row = createLearnerElement('div', 'is-' + status);
                        const marker = createLearnerElement('span');
                        const copy = createLearnerElement('p');
                        const title = createLearnerElement('strong', '', 'Modulo ' + module.number);
                        const subtitle = createLearnerElement('small', '', module.title);
                        const result = createLearnerElement('b');
                        const resultDetail = createLearnerElement('small');
                        const statusIcon = createLearnerElement('i');

                        if (status === 'locked') {
                            const markerIcon = createLearnerElement('i', 'fa-solid fa-lock');
                            markerIcon.setAttribute('aria-hidden', 'true');
                            marker.appendChild(markerIcon);
                        } else {
                            marker.textContent = String(module.number);
                        }

                        copy.append(title, subtitle);

                        if (status === 'approved') {
                            result.append(document.createTextNode((examState.score ?? 0) + '%'), createLearnerElement('small', '', 'Completado'));
                            statusIcon.className = 'fa-solid fa-check';
                        } else if (status === 'failed') {
                            result.append(document.createTextNode((examState.score ?? 0) + '%'), createLearnerElement('small', '', 'No aprobado'));
                            statusIcon.className = 'fa-solid fa-xmark';
                        } else if (status === 'current') {
                            result.textContent = 'En curso';
                            statusIcon.className = 'fa-solid fa-arrow-right';
                        } else {
                            result.textContent = 'Bloqueado';
                            statusIcon.className = 'fa-solid fa-lock';
                        }

                        statusIcon.setAttribute('aria-hidden', 'true');
                        row.append(marker, copy, result, statusIcon);
                        history.appendChild(row);
                    });

                    const passedModules = learnerCurriculum.filter(isModulePassed).length;
                    const certificateReady = passedModules === learnerCurriculum.length;
                    const certificateRow = createLearnerElement('div', certificateReady ? 'is-approved' : 'is-locked');
                    const certificateMarker = createLearnerElement('span');
                    const certificateMarkerIcon = createLearnerElement('i', certificateReady ? 'fa-solid fa-award' : 'fa-solid fa-lock');
                    const certificateCopy = createLearnerElement('p');
                    const certificateResult = createLearnerElement('b', '', certificateReady ? 'Disponible' : 'Bloqueado');
                    const certificateStatusIcon = createLearnerElement('i', certificateReady ? 'fa-solid fa-check' : 'fa-solid fa-lock');

                    certificateMarkerIcon.setAttribute('aria-hidden', 'true');
                    certificateStatusIcon.setAttribute('aria-hidden', 'true');
                    certificateMarker.appendChild(certificateMarkerIcon);
                    certificateCopy.append(
                        createLearnerElement('strong', '', 'Resultado final'),
                        createLearnerElement('small', '', 'Certificacion')
                    );
                    certificateRow.append(certificateMarker, certificateCopy, certificateResult, certificateStatusIcon);
                    history.appendChild(certificateRow);
                }

                function renderLearnerResults(obligation) {
                    const latestResult = getLatestLearnerResult();

                    if (!latestResult) {
                        return;
                    }

                    const score = Number(latestResult.state.score) || 0;
                    const passed = latestResult.state.status === 'passed';
                    const hero = root.querySelector('[data-result-hero]');
                    const ring = root.querySelector('[data-result-ring]');
                    const status = root.querySelector('[data-result-status]');
                    const statusIcon = createLearnerElement('i', passed ? 'fa-solid fa-check' : 'fa-solid fa-xmark');
                    const primaryAction = root.querySelector('[data-result-primary-action]');
                    const reviewButton = root.querySelector('[data-review-results]');
                    const answers = Array.isArray(latestResult.state.answers) ? latestResult.state.answers : [];
                    const breakdownScores = [score, Math.max(0, score - 3), Math.min(100, score + 2)];

                    hero.classList.toggle('is-failed', !passed);
                    ring.style.setProperty('--score', String(score));
                    root.querySelector('[data-result-score]').textContent = score + '%';
                    statusIcon.setAttribute('aria-hidden', 'true');
                    status.className = 'training-state-pill ' + (passed ? 'is-approved' : 'is-corrections');
                    status.replaceChildren(statusIcon, document.createTextNode(passed ? ' Modulo aprobado' : ' Modulo no aprobado'));
                    root.querySelector('[data-result-module]').textContent = latestResult.module.title;
                    root.querySelector('[data-result-message-primary]').textContent = passed
                        ? 'Superaste el minimo aprobatorio de ' + latestResult.exam.minimum + '%.'
                        : 'Obtuviste ' + score + '%. Necesitas al menos ' + latestResult.exam.minimum + '% para aprobar.';
                    root.querySelector('[data-result-message-secondary]').textContent = passed
                        ? (obligation.type === 'certificate'
                            ? 'Terminaste el programa y tu certificado esta disponible.'
                            : 'El modulo ' + obligation.module.number + ' ya esta disponible.')
                        : 'Revisa tus respuestas y vuelve a presentar el examen.';
                    primaryAction.textContent = getObligationActionLabel(obligation);
                    reviewButton.disabled = answers.length === 0;

                    root.querySelectorAll('[data-result-breakdown-fill]').forEach(function(fill, index) {
                        fill.style.width = breakdownScores[index] + '%';
                    });
                    root.querySelectorAll('[data-result-breakdown-score]').forEach(function(label, index) {
                        label.textContent = breakdownScores[index] + '%';
                    });
                    root.querySelector('[data-result-attempt]').textContent = String(latestResult.state.attempts || 1);
                    root.querySelector('[data-result-pass-state]').textContent = passed ? 'Aprobado' : 'No aprobado';
                    root.querySelector('[data-result-correct-answers]').textContent = (latestResult.state.correct ?? 0)
                        + '/' + (latestResult.state.total || latestResult.exam.items.length);

                    renderLearnerResultHistory();
                }

                function renderLearnerCertificate(obligation) {
                    const approvedModules = learnerCurriculum.filter(isModulePassed).length;
                    const isAvailable = approvedModules === learnerCurriculum.length;
                    const progress = learnerCurriculum.length
                        ? Math.round((approvedModules / learnerCurriculum.length) * 100)
                        : 0;
                    const card = root.querySelector('[data-certificate-card]');
                    const status = root.querySelector('[data-certificate-status]');
                    const statusIcon = createLearnerElement('i', isAvailable ? 'fa-solid fa-check' : 'fa-solid fa-lock');
                    const action = root.querySelector('[data-certificate-action]');

                    card.classList.toggle('is-available', isAvailable);
                    root.querySelector('[data-certificate-message]').textContent = isAvailable
                        ? 'Completaste los contenidos, tareas y examenes de los cuatro modulos.'
                        : 'Completa los cuatro modulos y sus evaluaciones para emitir el certificado.';
                    root.querySelector('[data-certificate-progress-fill]').style.width = progress + '%';
                    root.querySelector('[data-certificate-progress-track]').setAttribute('aria-label', 'Progreso del certificado: ' + progress + '%');
                    root.querySelector('[data-certificate-progress-label]').textContent = approvedModules + ' de ' + learnerCurriculum.length + ' modulos';
                    statusIcon.setAttribute('aria-hidden', 'true');
                    status.className = 'training-state-pill ' + (isAvailable ? 'is-approved' : 'is-locked');
                    status.replaceChildren(statusIcon, document.createTextNode(isAvailable ? ' Disponible' : ' Pendiente'));
                    action.className = isAvailable ? 'training-primary-button' : 'training-secondary-button';
                    action.textContent = isAvailable ? 'Ver resultado final' : getObligationActionLabel(obligation);
                }

                function renderLearnerExperience() {
                    synchronizeLearnerState();
                    currentLearnerObligation = getNextLearnerObligation();

                    const overallProgress = getOverallLearnerProgress();
                    const approvedModules = learnerCurriculum.filter(isModulePassed).length;
                    const latestResult = getLatestLearnerResult();

                    root.querySelector('[data-overall-progress]').textContent = overallProgress + '%';
                    root.querySelector('[data-overall-progress-fill]').style.width = overallProgress + '%';
                    root.querySelector('[data-approved-module-count]').textContent = approvedModules + '/' + learnerCurriculum.length;
                    root.querySelector('[data-latest-exam-score]').textContent = latestResult ? latestResult.state.score + '%' : '-';

                    updateLearnerFlow(currentLearnerObligation);
                    renderLearnerHome(currentLearnerObligation);
                    renderLearnerModules(currentLearnerObligation);

                    learnerTaskRows.forEach(function(row) {
                        const storedTask = learnerState.tasks?.[row.dataset.learnerTask] || { status: 'locked' };
                        setTaskRowStatus(row, storedTask.status);
                    });

                    updateLearnerTasks();
                    renderLearnerResults(currentLearnerObligation);
                    renderLearnerCertificate(currentLearnerObligation);
                }

                function openLearnerExam(module) {
                    if (!module || !learnerExamDialog || !learnerExamForm || !learnerExamQuestions) {
                        return;
                    }

                    const examState = learnerState.exams?.[module.examId] || {};

                    if (!['available', 'failed'].includes(examState.status)) {
                        return;
                    }

                    const exam = getLearnerExam(module);
                    activeLearnerExamModule = module;
                    learnerExamForm.reset();
                    learnerExamQuestions.replaceChildren();
                    learnerExamValidation.hidden = true;
                    root.querySelector('[data-learner-exam-title]').textContent = exam.name;
                    root.querySelector('[data-learner-exam-module]').textContent = 'Modulo ' + module.number + ' · ' + module.title;
                    root.querySelector('[data-learner-exam-minimum]').textContent = exam.minimum + '%';

                    exam.items.forEach(function(question, questionIndex) {
                        const fieldset = createLearnerElement('fieldset', 'training-exam-question');
                        const legend = createLearnerElement('legend', '', (questionIndex + 1) + '. ' + question.text);
                        const inputType = question.type === 'multiple' ? 'checkbox' : 'radio';

                        fieldset.appendChild(legend);

                        question.options.forEach(function(option, optionIndex) {
                            const label = createLearnerElement('label');
                            const input = createLearnerElement('input');
                            const copy = createLearnerElement('span', '', option);

                            input.type = inputType;
                            input.name = 'learner-question-' + questionIndex;
                            input.value = String(optionIndex);
                            label.append(input, copy);
                            fieldset.appendChild(label);
                        });

                        learnerExamQuestions.appendChild(fieldset);
                    });

                    openLearnerDialog(learnerExamDialog);
                    learnerExamQuestions.querySelector('input')?.focus();
                }

                function normalizeAnswerIndexes(answer) {
                    const values = Array.isArray(answer) ? answer : [answer];

                    return values.map(function(value) {
                        return Number.parseInt(value, 10);
                    }).filter(Number.isInteger).sort(function(first, second) {
                        return first - second;
                    });
                }

                function answersMatch(answer, correctAnswer) {
                    const selected = normalizeAnswerIndexes(answer);
                    const correct = normalizeAnswerIndexes(correctAnswer);

                    return selected.length === correct.length && selected.every(function(value, index) {
                        return value === correct[index];
                    });
                }

                function renderLearnerResultReview() {
                    const latestResult = getLatestLearnerResult();
                    const list = root.querySelector('[data-review-result-list]');

                    if (!latestResult || !list) {
                        return false;
                    }

                    const answers = Array.isArray(latestResult.state.answers) ? latestResult.state.answers : [];

                    if (!answers.length) {
                        return false;
                    }

                    root.querySelector('[data-review-result-eyebrow]').textContent = 'Resultado del modulo ' + latestResult.module.number;
                    root.querySelector('[data-review-result-summary]').textContent = latestResult.module.title + ' · '
                        + (latestResult.state.correct ?? 0) + ' de ' + (latestResult.state.total || latestResult.exam.items.length)
                        + ' respuestas correctas';
                    list.replaceChildren();

                    latestResult.exam.items.forEach(function(question, questionIndex) {
                        const answer = answers[questionIndex];
                        const isCorrect = answersMatch(answer, question.correct);
                        const selectedIndexes = normalizeAnswerIndexes(answer);
                        const correctIndexes = normalizeAnswerIndexes(question.correct);
                        const selectedCopy = selectedIndexes.map(function(index) {
                            return question.options[index];
                        }).filter(Boolean).join(', ').replace(/[.\s]+$/, '') || 'Sin respuesta';
                        const correctCopy = correctIndexes.map(function(index) {
                            return question.options[index];
                        }).filter(Boolean).join(', ').replace(/[.\s]+$/, '');
                        const item = createLearnerElement('article', isCorrect ? 'is-correct' : 'is-incorrect');
                        const number = createLearnerElement('span', '', String(questionIndex + 1));
                        const copy = createLearnerElement('div');
                        const title = createLearnerElement('strong', '', question.text);
                        const detail = createLearnerElement('p', '', 'Tu respuesta: ' + selectedCopy + '. Respuesta correcta: ' + correctCopy + '.');
                        const icon = createLearnerElement('i', isCorrect ? 'fa-solid fa-check' : 'fa-solid fa-xmark');

                        icon.setAttribute('aria-hidden', 'true');
                        copy.append(title, detail);
                        item.append(number, copy, icon);
                        list.appendChild(item);
                    });

                    return true;
                }

                function dispatchLearnerObligation() {
                    const obligation = currentLearnerObligation;

                    if (!obligation) {
                        return;
                    }

                    if (obligation.type === 'content') {
                        openLearnerVideoDialog(true);
                        return;
                    }

                    if (obligation.type === 'task') {
                        activateLearnerTab('tasks', true);
                        const allFilter = root.querySelector('[data-task-filter="all"]');
                        learnerTaskFilters.forEach(function(button) {
                            button.classList.toggle('is-active', button === allFilter);
                        });
                        updateLearnerTasks();

                        const taskRow = Array.from(learnerTaskRows).find(function(row) {
                            return row.dataset.learnerTask === obligation.taskId;
                        });

                        if (taskRow) {
                            openLearnerTask(taskRow, false);
                        }
                        return;
                    }

                    if (obligation.type === 'exam') {
                        openLearnerExam(obligation.module);
                        return;
                    }

                    activateLearnerTab('certificates', true);
                }

                function openLearnerDialog(dialog) {
                    if (!dialog || dialog.open) {
                        return;
                    }

                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute('open', '');
                    }
                }

                function closeLearnerDialog(dialog) {
                    if (!dialog || !dialog.open) {
                        return;
                    }

                    if (typeof dialog.close === 'function') {
                        dialog.close();
                    } else {
                        dialog.removeAttribute('open');
                    }
                }

                function activateLearnerTab(tabId, moveFocus) {
                    const requestedPanel = root.querySelector('[data-learner-panel="' + tabId + '"]');
                    const activeId = requestedPanel ? tabId : 'home';

                    learnerTabButtons.forEach(function(button) {
                        const isActive = button.dataset.learnerTab === activeId;

                        button.classList.toggle('is-active', isActive);
                        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        button.tabIndex = isActive ? 0 : -1;

                        if (isActive && moveFocus) {
                            button.focus();
                        }
                    });

                    learnerPanels.forEach(function(panel) {
                        panel.hidden = panel.dataset.learnerPanel !== activeId;
                    });

                    if (activeId === 'tasks') {
                        updateLearnerTasks();
                    }
                }

                function createTaskStatusPill(status) {
                    const settings = {
                        approved: ['is-approved', 'fa-regular fa-circle-check', 'Aprobada'],
                        pending: ['is-pending', 'fa-regular fa-clock', 'Pendiente'],
                        review: ['is-review', 'fa-regular fa-clock', 'En revision'],
                        corrections: ['is-corrections', 'fa-solid fa-rotate-left', 'Correcciones'],
                        locked: ['is-locked', 'fa-solid fa-lock', 'Bloqueada'],
                    }[status] || ['is-locked', 'fa-solid fa-lock', 'Bloqueada'];
                    const pill = createLearnerElement('span', 'training-state-pill ' + settings[0]);
                    const icon = createLearnerElement('i', settings[1]);

                    icon.setAttribute('aria-hidden', 'true');
                    pill.append(icon, document.createTextNode(' ' + settings[2]));

                    return pill;
                }

                function setTaskRowStatus(row, status) {
                    const normalizedStatus = ['approved', 'pending', 'review', 'corrections', 'locked'].includes(status)
                        ? status
                        : 'locked';
                    const statusCell = row.querySelector('[data-task-status-cell]');
                    const actionCell = row.querySelector('[data-task-action-cell]');
                    const action = createLearnerElement('button');

                    row.dataset.taskStatus = normalizedStatus;
                    row.classList.remove('is-approved', 'is-pending', 'is-review', 'is-corrections', 'is-locked');
                    row.classList.add('is-' + normalizedStatus);
                    statusCell?.replaceChildren(createTaskStatusPill(normalizedStatus));

                    if (!actionCell) {
                        return;
                    }

                    action.type = 'button';

                    if (normalizedStatus === 'pending' || normalizedStatus === 'corrections') {
                        action.className = 'training-primary-button';
                        action.dataset.openTask = '';
                        action.textContent = normalizedStatus === 'corrections' ? 'Corregir tarea' : 'Realizar tarea';
                    } else if (normalizedStatus === 'review' || normalizedStatus === 'approved') {
                        action.className = 'training-secondary-button';
                        action.dataset.viewTask = '';
                        action.textContent = 'Ver entrega';
                    } else {
                        action.className = 'training-secondary-button';
                        action.disabled = true;
                        action.textContent = 'No disponible';
                    }

                    actionCell.replaceChildren(action);
                }

                function updateLearnerTasks() {
                    const activeFilter = root.querySelector('[data-task-filter].is-active')?.dataset.taskFilter || 'all';
                    let visibleTasks = 0;
                    let activeTasks = 0;

                    learnerTaskRows.forEach(function(row) {
                        const status = row.dataset.taskStatus;
                        const isVisible = activeFilter === 'all' || status === activeFilter;

                        row.hidden = !isVisible;

                        if (isVisible) {
                            visibleTasks += 1;
                        }

                        if (status === 'pending' || status === 'corrections') {
                            activeTasks += 1;
                        }
                    });

                    if (learnerTaskEmptyRow) {
                        learnerTaskEmptyRow.hidden = visibleTasks !== 0;
                    }

                    root.querySelectorAll('[data-learner-open-task-count]').forEach(function(element) {
                        element.textContent = String(activeTasks);
                    });

                    const summary = root.querySelector('[data-active-task-summary]');

                    if (summary) {
                        summary.textContent = activeTasks + (activeTasks === 1 ? ' tarea activa' : ' tareas activas');
                    }
                }

                function openLearnerTask(row, readOnly) {
                    if (!row || !learnerTaskDialog || !learnerTaskForm || !learnerTaskSubmissionView) {
                        return;
                    }

                    activeLearnerTaskRow = row;
                    const taskId = row.dataset.learnerTask;
                    const savedTask = learnerState.tasks?.[taskId] || {};
                    const title = learnerTaskDialog.querySelector('[data-task-dialog-title]');
                    const eyebrow = learnerTaskDialog.querySelector('[data-task-dialog-eyebrow]');
                    const module = learnerTaskDialog.querySelector('[data-task-dialog-module]');
                    const taskIdInput = learnerTaskDialog.querySelector('[data-task-dialog-id]');
                    const response = learnerTaskDialog.querySelector('[data-task-response]');
                    const submittedResponse = learnerTaskDialog.querySelector('[data-task-submitted-response]');
                    const submittedFile = learnerTaskDialog.querySelector('[data-task-submitted-file]');

                    title.textContent = row.dataset.taskTitle;
                    module.textContent = row.dataset.taskModule;

                    if (readOnly) {
                        eyebrow.textContent = 'Entrega de tarea';
                        learnerTaskForm.hidden = true;
                        learnerTaskSubmissionView.hidden = false;
                        submittedResponse.textContent = savedTask.response || 'Actividad completada y validada por el responsable de capacitacion.';
                        submittedFile.textContent = savedTask.fileName || (row.dataset.taskStatus === 'approved' ? 'lista_verificacion.pdf' : 'Sin archivo adjunto');
                    } else {
                        eyebrow.textContent = row.dataset.taskStatus === 'corrections' ? 'Corregir tarea' : 'Realizar tarea';
                        learnerTaskForm.hidden = false;
                        learnerTaskSubmissionView.hidden = true;
                        learnerTaskForm.reset();
                        taskIdInput.value = taskId;
                        response.value = savedTask.response || '';
                    }

                    openLearnerDialog(learnerTaskDialog);

                    if (!readOnly) {
                        window.requestAnimationFrame(function() {
                            response.focus();
                        });
                    }
                }

                function createModuleStatePill(status) {
                    const settings = {
                        approved: ['is-approved', 'fa-solid fa-check', 'Aprobado'],
                        current: ['is-current', 'fa-solid fa-play', 'En curso'],
                        locked: ['is-locked', 'fa-solid fa-lock', 'Bloqueado'],
                    }[status] || ['is-locked', 'fa-solid fa-lock', 'Bloqueado'];
                    const pill = createLearnerElement('span', 'training-state-pill ' + settings[0]);
                    const icon = createLearnerElement('i', settings[1]);

                    icon.setAttribute('aria-hidden', 'true');
                    pill.append(icon, document.createTextNode(' ' + settings[2]));

                    return pill;
                }

                learnerTabButtons.forEach(function(button, buttonIndex) {
                    button.addEventListener('click', function() {
                        activateLearnerTab(button.dataset.learnerTab, false);
                    });

                    button.addEventListener('keydown', function(event) {
                        const supportedKeys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];

                        if (!supportedKeys.includes(event.key)) {
                            return;
                        }

                        event.preventDefault();
                        let nextIndex = buttonIndex;

                        if (event.key === 'Home') {
                            nextIndex = 0;
                        } else if (event.key === 'End') {
                            nextIndex = learnerTabButtons.length - 1;
                        } else {
                            const direction = event.key === 'ArrowRight' ? 1 : -1;
                            nextIndex = (buttonIndex + direction + learnerTabButtons.length) % learnerTabButtons.length;
                        }

                        activateLearnerTab(learnerTabButtons[nextIndex].dataset.learnerTab, true);
                    });
                });

                openLearnerPanelButton?.addEventListener('click', openSelectedLearnerPanel);

                learnerSelector?.addEventListener('change', function() {
                    if (openLearnerPanelButton) {
                        openLearnerPanelButton.disabled = !learnerSelector.value;
                    }
                });

                root.querySelectorAll('[data-close-learner-video]').forEach(function(button) {
                    button.addEventListener('click', closeLearnerVideoDialog);
                });

                learnerVideoDialog?.addEventListener('click', function(event) {
                    if (event.target === learnerVideoDialog) {
                        closeLearnerVideoDialog();
                    }
                });

                learnerVideoDialog?.addEventListener('close', function() {
                    persistCurrentVideoProgress();
                    stopLearnerPlayback(false);
                    updateLearnerPlaybackControls();
                });

                window.addEventListener('pagehide', persistCurrentVideoProgress);

                learnerTaskFilters.forEach(function(button) {
                    button.addEventListener('click', function() {
                        learnerTaskFilters.forEach(function(filterButton) {
                            filterButton.classList.toggle('is-active', filterButton === button);
                        });
                        updateLearnerTasks();
                    });
                });

                root.addEventListener('click', function(event) {
                    const goToButton = event.target.closest('[data-learner-go-to]');
                    const openTaskButton = event.target.closest('[data-open-task]');
                    const viewTaskButton = event.target.closest('[data-view-task]');
                    const obligationButton = event.target.closest('[data-next-obligation-action], [data-result-primary-action], [data-module-primary-action]');
                    const outlineVideo = event.target.closest('[data-outline-video].is-current');
                    const certificateAction = event.target.closest('[data-certificate-action]');

                    if (goToButton && root.contains(goToButton)) {
                        activateLearnerTab(goToButton.dataset.learnerGoTo, true);
                    }

                    if (openTaskButton && root.contains(openTaskButton)) {
                        openLearnerTask(openTaskButton.closest('[data-learner-task]'), false);
                    }

                    if (viewTaskButton && root.contains(viewTaskButton)) {
                        openLearnerTask(viewTaskButton.closest('[data-learner-task]'), true);
                    }

                    if (obligationButton && root.contains(obligationButton)) {
                        dispatchLearnerObligation();
                    }

                    if (outlineVideo && root.contains(outlineVideo)) {
                        openLearnerVideoDialog(true);
                    }

                    if (certificateAction && root.contains(certificateAction)) {
                        if (currentLearnerObligation?.type === 'certificate') {
                            activateLearnerTab('results', true);
                        } else {
                            dispatchLearnerObligation();
                        }
                    }
                });

                root.querySelectorAll('[data-close-task-dialog]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        closeLearnerDialog(learnerTaskDialog);
                    });
                });

                if (learnerTaskDialog) {
                    learnerTaskDialog.addEventListener('click', function(event) {
                        if (event.target === learnerTaskDialog) {
                            closeLearnerDialog(learnerTaskDialog);
                        }
                    });

                    learnerTaskDialog.addEventListener('close', function() {
                        activeLearnerTaskRow = null;
                    });
                }

                learnerTaskForm?.addEventListener('submit', function(event) {
                    event.preventDefault();

                    if (!activeLearnerTaskRow) {
                        return;
                    }

                    const taskId = activeLearnerTaskRow.dataset.learnerTask;
                    const response = learnerTaskForm.querySelector('[data-task-response]').value.trim();
                    const evidence = learnerTaskForm.querySelector('[data-task-evidence]');

                    if (!response) {
                        learnerTaskForm.querySelector('[data-task-response]').focus();
                        return;
                    }

                    learnerState.tasks = learnerState.tasks || {};
                    learnerState.tasks[taskId] = {
                        status: 'review',
                        response: response,
                        fileName: evidence.files?.[0]?.name || '',
                    };
                    persistLearnerState();
                    renderLearnerExperience();
                    openLearnerTask(activeLearnerTaskRow, true);
                });

                root.querySelector('[data-training-video-play]')?.addEventListener('click', function(event) {
                    if (currentLearnerObligation?.type !== 'content' || learnerPlaybackProgress >= 100) {
                        return;
                    }

                    if (learnerPlaybackTimer) {
                        stopLearnerPlayback(false);
                        persistCurrentVideoProgress();
                        updateLearnerPlaybackControls();
                        return;
                    }

                    learnerPlaybackTimer = window.setInterval(function() {
                        learnerPlaybackProgress = Math.min(100, learnerPlaybackProgress + 4);

                        if (learnerPlaybackProgress >= 100) {
                            stopLearnerPlayback(false);
                            persistCurrentVideoProgress();
                        }

                        updateLearnerPlaybackControls();
                    }, 120);
                    updateLearnerPlaybackControls();
                });

                root.querySelector('[data-complete-training-video]')?.addEventListener('click', function() {
                    if (currentLearnerObligation?.type !== 'content' || learnerPlaybackProgress < 100) {
                        return;
                    }

                    learnerState.completedVideos = learnerState.completedVideos || [];
                    learnerState.videoProgress = learnerState.videoProgress || {};
                    learnerState.videoProgress[currentLearnerObligation.video.id] = 100;
                    learnerState.completedVideos.push(currentLearnerObligation.video.id);
                    learnerState.completedVideos = Array.from(new Set(learnerState.completedVideos));
                    persistLearnerState();
                    closeLearnerVideoDialog();
                    renderLearnerExperience();
                    activateLearnerTab('training', true);
                });

                root.querySelectorAll('[data-close-learner-exam]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        closeLearnerDialog(learnerExamDialog);
                    });
                });

                learnerExamDialog?.addEventListener('click', function(event) {
                    if (event.target === learnerExamDialog) {
                        closeLearnerDialog(learnerExamDialog);
                    }
                });

                learnerExamDialog?.addEventListener('close', function() {
                    activeLearnerExamModule = null;
                });

                learnerExamForm?.addEventListener('submit', function(event) {
                    event.preventDefault();

                    if (!activeLearnerExamModule) {
                        return;
                    }

                    const module = activeLearnerExamModule;
                    const exam = getLearnerExam(module);
                    const answers = [];
                    let correctAnswers = 0;
                    let earnedPoints = 0;
                    let totalPoints = 0;
                    let firstIncompleteQuestion = null;

                    exam.items.forEach(function(question, questionIndex) {
                        const inputs = Array.from(learnerExamForm.querySelectorAll('[name="learner-question-' + questionIndex + '"]'));
                        const selected = inputs.filter(function(input) {
                            return input.checked;
                        }).map(function(input) {
                            return Number.parseInt(input.value, 10);
                        });
                        const answer = question.type === 'multiple' ? selected : selected[0];
                        const points = Math.max(1, Number(question.points) || 1);

                        if (!selected.length && !firstIncompleteQuestion) {
                            firstIncompleteQuestion = inputs[0]?.closest('.training-exam-question') || null;
                        }

                        answers.push(answer);
                        totalPoints += points;

                        if (selected.length && answersMatch(answer, question.correct)) {
                            correctAnswers += 1;
                            earnedPoints += points;
                        }
                    });

                    if (firstIncompleteQuestion) {
                        learnerExamValidation.hidden = false;
                        firstIncompleteQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstIncompleteQuestion.querySelector('input')?.focus();
                        return;
                    }

                    const score = totalPoints ? Math.round((earnedPoints / totalPoints) * 100) : 0;
                    const previousExamState = learnerState.exams?.[module.examId] || {};

                    learnerState.exams[module.examId] = {
                        status: score >= exam.minimum ? 'passed' : 'failed',
                        score: score,
                        correct: correctAnswers,
                        total: exam.items.length,
                        attempts: (Number(previousExamState.attempts) || 0) + 1,
                        answers: answers,
                    };
                    persistLearnerState();
                    closeLearnerDialog(learnerExamDialog);
                    renderLearnerExperience();
                    activateLearnerTab('results', true);
                });

                root.querySelector('[data-review-results]')?.addEventListener('click', function() {
                    if (renderLearnerResultReview()) {
                        openLearnerDialog(resultReviewDialog);
                    }
                });

                root.querySelectorAll('[data-close-result-review]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        closeLearnerDialog(resultReviewDialog);
                    });
                });

                resultReviewDialog?.addEventListener('click', function(event) {
                    if (event.target === resultReviewDialog) {
                        closeLearnerDialog(resultReviewDialog);
                    }
                });

                renderActiveLearnerIdentity();
                renderLearnerExperience();

                function activateRole(role) {
                    buttons.forEach(function(button) {
                        button.classList.toggle('is-active', button.dataset.roleButton === role);
                    });

                    panels.forEach(function(panel) {
                        panel.hidden = panel.dataset.rolePanel !== role;
                    });
                }

                buttons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        activateRole(button.dataset.roleButton);
                    });
                });

                function activateProgramTab(tab) {
                    if (tab !== 'exams' && examCreateView && !examCreateView.hidden) {
                        closeNewExamBuilder();
                    }

                    programButtons.forEach(function(button) {
                        button.classList.toggle('is-active', button.dataset.programTab === tab);
                    });

                    programPanels.forEach(function(panel) {
                        panel.hidden = panel.dataset.programPanel !== tab;
                    });
                }

                function formatVideoDuration(durationInSeconds) {
                    const totalSeconds = Math.max(1, Math.round(durationInSeconds));
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;
                    const parts = [];

                    if (hours) {
                        parts.push(hours + ' h');
                    }

                    if (minutes) {
                        parts.push(minutes + ' min');
                    }

                    if (seconds || !parts.length) {
                        parts.push(seconds + ' s');
                    }

                    return parts.join(' ');
                }

                function updateChapterDurationFromVideo(input) {
                    const chapter = input.closest('[data-training-chapter]');

                    if (!chapter) {
                        return;
                    }

                    const durationInput = chapter.querySelector('[data-chapter-duration]');
                    const uploadControl = input.closest('.training-upload-control');
                    const uploadStatus = chapter.querySelector('[data-chapter-upload-status]');

                    if (!durationInput || !uploadControl || !uploadStatus) {
                        return;
                    }

                    if (!uploadStatus.dataset.initialText) {
                        uploadStatus.dataset.initialText = uploadStatus.textContent.trim();
                    }

                    const file = input.files && input.files[0];
                    const requestToken = Date.now().toString(36) + Math.random().toString(36).slice(2);

                    input.dataset.durationRequest = requestToken;
                    uploadControl.classList.remove('is-reading', 'has-video', 'has-error');

                    if (!file) {
                        durationInput.value = durationInput.dataset.initialDuration || '';
                        durationInput.dataset.lastDuration = durationInput.value;
                        delete durationInput.dataset.durationSeconds;
                        uploadStatus.textContent = uploadStatus.dataset.initialText;

                        return;
                    }

                    const previousDuration = durationInput.dataset.lastDuration ||
                        durationInput.value || durationInput.dataset.initialDuration || '';
                    const objectUrl = window.URL.createObjectURL(file);
                    const video = document.createElement('video');
                    let completed = false;
                    let timeoutId;

                    durationInput.value = 'Calculando...';
                    uploadStatus.textContent = file.name + ' | leyendo duracion...';
                    uploadControl.classList.add('is-reading');
                    video.preload = 'metadata';

                    function cleanup() {
                        window.clearTimeout(timeoutId);
                        window.URL.revokeObjectURL(objectUrl);
                    }

                    function showReadError() {
                        if (completed) {
                            return;
                        }

                        completed = true;
                        cleanup();

                        if (input.dataset.durationRequest !== requestToken) {
                            return;
                        }

                        durationInput.value = previousDuration;
                        uploadStatus.textContent = file.name + ' | no se pudo leer la duracion';
                        uploadControl.classList.remove('is-reading', 'has-video');
                        uploadControl.classList.add('has-error');
                    }

                    video.addEventListener('loadedmetadata', function() {
                        if (completed) {
                            return;
                        }

                        if (!Number.isFinite(video.duration) || video.duration <= 0) {
                            showReadError();

                            return;
                        }

                        completed = true;
                        cleanup();

                        if (input.dataset.durationRequest !== requestToken) {
                            return;
                        }

                        const formattedDuration = formatVideoDuration(video.duration);

                        durationInput.value = formattedDuration;
                        durationInput.dataset.lastDuration = formattedDuration;
                        durationInput.dataset.durationSeconds = String(Math.round(video.duration));
                        uploadStatus.textContent = file.name + ' | ' + formattedDuration;
                        uploadControl.classList.remove('is-reading', 'has-error');
                        uploadControl.classList.add('has-video');
                    }, { once: true });

                    video.addEventListener('error', showReadError, { once: true });
                    timeoutId = window.setTimeout(showReadError, 15000);
                    video.src = objectUrl;
                }

                function activateModuleItem(item) {
                    moduleItems.forEach(function(moduleItem) {
                        moduleItem.classList.toggle('is-active', moduleItem === item);
                    });

                    if (moduleEditor) {
                        moduleEditor.hidden = !item;
                    }

                    if (!item) {
                        return;
                    }

                    root.querySelector('[data-module-editor-label]').textContent = item.dataset.moduleLabel;
                    root.querySelector('[data-module-editor-title]').textContent = item.dataset.moduleTitle;
                    root.querySelector('[data-module-label-input]').value = item.dataset.moduleLabel;
                    root.querySelector('[data-module-title-input]').value = item.dataset.moduleTitle;
                    root.querySelector('[data-module-description-input]').value = item.dataset.moduleDescription;
                }

                function filterProgramRows(panelName, programId) {
                    const panel = root.querySelector('[data-program-panel="' + panelName + '"]');

                    if (!panel) {
                        return;
                    }

                    let visibleRows = 0;

                    panel.querySelectorAll('[data-program-row]').forEach(function(row) {
                        const isVisible = row.dataset.programRow === programId;

                        row.hidden = !isVisible;

                        if (isVisible) {
                            visibleRows += 1;
                        }
                    });

                    const emptyRow = panel.querySelector('[data-program-empty-row]');

                    if (emptyRow) {
                        emptyRow.hidden = visibleRows > 0;
                    }
                }

                function selectProgram(programId, persistSelection) {
                    if (!programSelector || !programSelector.options.length) {
                        return;
                    }

                    const selectedOption = Array.from(programSelector.options).find(function(option) {
                        return option.value === programId;
                    }) || programSelector.options[0];

                    programSelector.value = selectedOption.value;
                    updateSelectedProgramName(selectedOption.textContent.trim());

                    let selectedProgramCard = null;

                    root.querySelectorAll('[data-program-card]').forEach(function(card) {
                        const isSelected = card.dataset.programId === selectedOption.value;
                        const selectionButton = card.querySelector('[data-program-carousel-item]');

                        card.classList.toggle('is-active', isSelected);

                        if (selectionButton) {
                            selectionButton.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                        }

                        if (isSelected) {
                            selectedProgramCard = card;
                        }
                    });

                    const visibleModules = [];

                    moduleItems.forEach(function(item) {
                        const isVisible = item.dataset.programId === selectedOption.value;

                        item.hidden = !isVisible;
                        item.classList.remove('is-active');

                        if (isVisible) {
                            visibleModules.push(item);
                        }
                    });

                    if (moduleCount) {
                        moduleCount.textContent = visibleModules.length + (visibleModules.length === 1 ? ' modulo' : ' modulos');
                    }

                    activateModuleItem(visibleModules[0] || null);
                    filterProgramRows('exams', selectedOption.value);
                    filterProgramRows('tasks', selectedOption.value);

                    if (selectedProgramCard) {
                        revealProgramCard(selectedProgramCard);
                    }

                    if (persistSelection !== false) {
                        try {
                            window.localStorage.setItem(selectedProgramStorageKey, selectedOption.value);
                        } catch (error) {
                            // The selection remains active even when browser storage is unavailable.
                        }
                    }
                }

                programButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        activateProgramTab(button.dataset.programTab);
                    });
                });

                if (programCarouselViewport) {
                    programCarouselViewport.addEventListener('click', function(event) {
                        const editButton = event.target.closest('[data-edit-program]');
                        const programButton = event.target.closest('[data-program-carousel-item]');

                        if (editButton) {
                            openProgramEditor(editButton.closest('[data-program-card]'));

                            return;
                        }

                        if (!programButton) {
                            return;
                        }

                        if (examCreateView && !examCreateView.hidden) {
                            closeNewExamBuilder();
                            activateProgramTab('exams');
                        }

                        selectProgram(programButton.dataset.programId);
                    });
                }

                if (newProgramButton) {
                    newProgramButton.addEventListener('click', openNewProgramModal);
                }

                if (programSelector) {
                    programSelector.addEventListener('change', function() {
                        selectProgram(programSelector.value);
                    });
                }

                moduleItems.forEach(function(item) {
                    item.addEventListener('click', function() {
                        activateModuleItem(item);
                    });
                });

                chapterVideoInputs.forEach(function(input) {
                    const chapter = input.closest('[data-training-chapter]');
                    const durationInput = chapter ? chapter.querySelector('[data-chapter-duration]') : null;

                    if (durationInput) {
                        durationInput.dataset.lastDuration = durationInput.value;
                    }

                    input.addEventListener('change', function() {
                        updateChapterDurationFromVideo(input);
                    });
                });

                const storedPrograms = readStoredPrograms();

                Object.keys(storedPrograms).forEach(function(programId) {
                    const savedProgram = storedPrograms[programId];
                    let card = root.querySelector('[data-program-card][data-program-id="' + programId + '"]');

                    if (!card && savedProgram.isCustom) {
                        ensureProgramOption(programId, savedProgram.title);
                        card = createProgramCarouselCard(programId, savedProgram);
                    }

                    if (card) {
                        applyProgramData(card, savedProgram);
                    }
                });

                root.querySelectorAll('[data-close-program-modal]').forEach(function(button) {
                    button.addEventListener('click', closeProgramModal);
                });

                if (programModal) {
                    programModal.addEventListener('click', function(event) {
                        if (event.target === programModal) {
                            closeProgramModal();
                        }
                    });
                }

                if (programForm) {
                    programForm.addEventListener('submit', function(event) {
                        event.preventDefault();

                        if (!programForm.reportValidity()) {
                            return;
                        }

                        let programId = programForm.elements.program_id.value;
                        const programs = readStoredPrograms();
                        const isNewProgram = !programId;

                        if (isNewProgram) {
                            programId = createProgramId(programForm.elements.title.value.trim());
                        }

                        const programData = Object.assign({}, programs[programId] || {}, {
                            id: programId,
                            title: programForm.elements.title.value.trim(),
                            description: programForm.elements.description.value.trim(),
                            owner: programForm.elements.owner.value.trim(),
                            status: programForm.elements.status.value,
                            isCustom: isNewProgram || Boolean(programs[programId]?.isCustom),
                            modules: programs[programId]?.modules || 0,
                            students: programs[programId]?.students || 0,
                        });
                        let card = root.querySelector('[data-program-card][data-program-id="' + programId + '"]');

                        ensureProgramOption(programId, programData.title);

                        if (!card) {
                            card = createProgramCarouselCard(programId, programData);
                        }

                        applyProgramData(card, programData);
                        programs[programId] = programData;

                        try {
                            window.localStorage.setItem(programStorageKey, JSON.stringify(programs));
                        } catch (error) {
                            // The card remains updated even when browser storage is unavailable.
                        }

                        selectProgram(programId);
                        activateProgramTab('modules');
                        closeProgramModal();
                    });
                }

                Object.keys(examRecords).forEach(function(examId) {
                    updateExamTableRow(examRecords[examId]);
                });

                if (examTableBody) {
                    examTableBody.addEventListener('click', function(event) {
                        const editButton = event.target.closest('[data-edit-exam]');
                        const viewButton = event.target.closest('[data-view-exam]');

                        if (editButton) {
                            openExamEditor(editButton.dataset.editExam);
                        }

                        if (viewButton) {
                            openExamView(viewButton.dataset.viewExam);
                        }
                    });
                }

                root.querySelectorAll('[data-close-exam-editor]').forEach(function(button) {
                    button.addEventListener('click', closeExamEditor);
                });

                root.querySelectorAll('[data-close-exam-view]').forEach(function(button) {
                    button.addEventListener('click', closeExamView);
                });

                if (examEditorModal) {
                    examEditorModal.addEventListener('click', function(event) {
                        if (event.target === examEditorModal) {
                            closeExamEditor();
                        }
                    });

                    examEditorModal.addEventListener('close', function() {
                        editingExam = null;
                    });
                }

                if (examViewModal) {
                    examViewModal.addEventListener('click', function(event) {
                        if (event.target === examViewModal) {
                            closeExamView();
                        }
                    });
                }

                if (examQuestionEditor) {
                    examQuestionEditor.addEventListener('input', function(event) {
                        if (!editingExam) {
                            return;
                        }

                        if (event.target.matches('[data-exam-question-text]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.examQuestionText, 10);

                            editingExam.items[questionIndex].text = event.target.value;
                        }

                        if (event.target.matches('[data-exam-option-text]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.examOptionText, 10);
                            const optionIndex = Number.parseInt(event.target.dataset.optionIndex, 10);

                            editingExam.items[questionIndex].options[optionIndex] = event.target.value;
                        }
                    });

                    examQuestionEditor.addEventListener('change', function(event) {
                        if (!editingExam || !event.target.matches('[data-exam-correct-option]')) {
                            return;
                        }

                        const questionIndex = Number.parseInt(event.target.dataset.examCorrectOption, 10);
                        const optionIndex = Number.parseInt(event.target.dataset.optionIndex, 10);
                        const question = editingExam.items[questionIndex];
                        const firstAnswer = examQuestionEditor.querySelector(
                            '[data-exam-correct-option="' + questionIndex + '"]'
                        );

                        if (firstAnswer) {
                            firstAnswer.setCustomValidity('');
                        }

                        if (question.type === 'multiple') {
                            if (event.target.checked && !question.correct.includes(optionIndex)) {
                                question.correct.push(optionIndex);
                            }

                            if (!event.target.checked) {
                                question.correct = question.correct.filter(function(answer) {
                                    return answer !== optionIndex;
                                });
                            }
                        } else {
                            question.correct = optionIndex;
                        }
                    });

                    examQuestionEditor.addEventListener('click', function(event) {
                        const button = event.target.closest('button');

                        if (!button || !editingExam) {
                            return;
                        }

                        if (button.dataset.removeExamQuestion !== undefined && editingExam.items.length > 1) {
                            const questionIndex = Number.parseInt(button.dataset.removeExamQuestion, 10);

                            editingExam.items.splice(questionIndex, 1);
                            renderExamQuestionEditor();

                            return;
                        }

                        if (button.dataset.addExamOption !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.addExamOption, 10);
                            const optionIndex = editingExam.items[questionIndex].options.length;

                            editingExam.items[questionIndex].options.push('');
                            renderExamQuestionEditor();

                            const optionInput = examQuestionEditor.querySelector(
                                '[data-exam-option-text="' + questionIndex + '"][data-option-index="' + optionIndex + '"]'
                            );

                            if (optionInput) {
                                optionInput.focus();
                            }

                            return;
                        }

                        if (button.dataset.removeExamOption !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.removeExamOption, 10);
                            const optionIndex = Number.parseInt(button.dataset.optionIndex, 10);
                            const question = editingExam.items[questionIndex];

                            if (question.options.length <= 2) {
                                return;
                            }

                            question.options.splice(optionIndex, 1);

                            if (question.type === 'multiple') {
                                question.correct = question.correct.filter(function(answer) {
                                    return answer !== optionIndex;
                                }).map(function(answer) {
                                    return answer > optionIndex ? answer - 1 : answer;
                                });

                                if (!question.correct.length) {
                                    question.correct = [0];
                                }
                            } else if (question.correct === optionIndex) {
                                question.correct = 0;
                            } else if (question.correct > optionIndex) {
                                question.correct -= 1;
                            }

                            renderExamQuestionEditor();
                        }
                    });
                }

                const addExamQuestion = root.querySelector('[data-add-exam-question]');

                if (addExamQuestion) {
                    addExamQuestion.addEventListener('click', function() {
                        if (!editingExam) {
                            return;
                        }

                        const questionIndex = editingExam.items.length;

                        editingExam.items.push({
                            text: '',
                            type: 'single',
                            points: 25,
                            options: ['', ''],
                            correct: 0,
                        });
                        renderExamQuestionEditor();

                        const questionInput = examQuestionEditor.querySelector(
                            '[data-exam-question-text="' + questionIndex + '"]'
                        );

                        if (questionInput) {
                            questionInput.focus();
                        }
                    });
                }

                if (examEditorForm) {
                    examEditorForm.addEventListener('submit', function(event) {
                        event.preventDefault();

                        if (!editingExam || !examEditorForm.reportValidity()) {
                            return;
                        }

                        const questionWithoutAnswer = editingExam.items.findIndex(function(question) {
                            return question.type === 'multiple' && !question.correct.length;
                        });

                        if (questionWithoutAnswer !== -1) {
                            const firstAnswer = examQuestionEditor.querySelector(
                                '[data-exam-correct-option="' + questionWithoutAnswer + '"]'
                            );

                            if (firstAnswer) {
                                firstAnswer.setCustomValidity('Selecciona al menos una respuesta correcta.');
                                firstAnswer.reportValidity();
                            }

                            return;
                        }

                        editingExam.module = examEditorForm.elements.module.value.trim();
                        editingExam.minimum = Number.parseInt(examEditorForm.elements.minimum.value, 10);
                        editingExam.items = editingExam.items.map(function(question) {
                            return {
                                text: question.text.trim(),
                                type: question.type,
                                points: question.points,
                                options: question.options.map(function(option) { return option.trim(); }),
                                correct: question.correct,
                            };
                        });
                        editingExam.questions = editingExam.items.length;

                        examRecords[editingExam.id] = normaliseExam(cloneExam(editingExam));
                        persistExams();
                        updateExamTableRow(examRecords[editingExam.id]);
                        closeExamEditor();
                    });
                }

                const newExamButton = root.querySelector('[data-new-exam]');
                const addNewExamQuestion = root.querySelector('[data-add-new-exam-question]');

                if (newExamButton) {
                    newExamButton.addEventListener('click', openNewExamBuilder);
                }

                root.querySelectorAll('[data-close-new-exam]').forEach(function(button) {
                    button.addEventListener('click', closeNewExamBuilder);
                });

                if (newExamModule) {
                    newExamModule.addEventListener('change', function() {
                        if (!creatingExam || !newExamForm) {
                            return;
                        }

                        const previousSuggestion = creatingExam.module
                            ? 'Evaluacion de ' + creatingExam.module.toLowerCase()
                            : '';
                        const shouldUpdateName = !newExamForm.elements.name.value.trim() ||
                            newExamForm.elements.name.value.trim() === previousSuggestion;

                        creatingExam.module = newExamModule.value;

                        if (shouldUpdateName) {
                            creatingExam.name = creatingExam.module
                                ? 'Evaluacion de ' + creatingExam.module.toLowerCase()
                                : '';
                            newExamForm.elements.name.value = creatingExam.name;
                        }
                    });
                }

                if (addNewExamQuestion) {
                    addNewExamQuestion.addEventListener('click', function() {
                        if (!creatingExam) {
                            return;
                        }

                        const questionIndex = creatingExam.items.length;

                        creatingExam.items.push(createNewExamQuestion('single'));
                        rebalanceNewExamPoints();
                        renderNewExamQuestions();

                        const questionInput = newExamQuestions.querySelector(
                            '[data-new-question-text="' + questionIndex + '"]'
                        );

                        if (questionInput) {
                            questionInput.focus();
                        }
                    });
                }

                if (newExamQuestions) {
                    newExamQuestions.addEventListener('input', function(event) {
                        if (!creatingExam) {
                            return;
                        }

                        if (event.target.matches('[data-new-question-text]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.newQuestionText, 10);

                            creatingExam.items[questionIndex].text = event.target.value;
                        }

                        if (event.target.matches('[data-new-question-points]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.newQuestionPoints, 10);

                            creatingExam.items[questionIndex].points = Number.parseInt(event.target.value, 10) || 0;
                            updateNewExamSummary();
                        }

                        if (event.target.matches('[data-new-question-option]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.newQuestionOption, 10);
                            const optionIndex = Number.parseInt(event.target.dataset.optionIndex, 10);

                            creatingExam.items[questionIndex].options[optionIndex] = event.target.value;
                        }
                    });

                    newExamQuestions.addEventListener('change', function(event) {
                        if (!creatingExam) {
                            return;
                        }

                        if (event.target.matches('[data-new-question-type]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.newQuestionType, 10);
                            const question = creatingExam.items[questionIndex];

                            question.type = event.target.value;

                            if (question.type === 'boolean') {
                                question.options = ['Verdadero', 'Falso'];
                                question.correct = 0;
                            } else if (question.type === 'multiple') {
                                while (question.options.length < 2) {
                                    question.options.push('');
                                }

                                question.correct = [Array.isArray(question.correct) ? (question.correct[0] ?? 0) : question.correct];
                            } else {
                                while (question.options.length < 2) {
                                    question.options.push('');
                                }

                                question.correct = Array.isArray(question.correct) ? (question.correct[0] ?? 0) : question.correct;
                            }

                            renderNewExamQuestions();

                            return;
                        }

                        if (event.target.matches('[data-new-question-correct]')) {
                            const questionIndex = Number.parseInt(event.target.dataset.newQuestionCorrect, 10);
                            const optionIndex = Number.parseInt(event.target.dataset.optionIndex, 10);
                            const question = creatingExam.items[questionIndex];

                            if (question.type === 'multiple') {
                                if (event.target.checked && !question.correct.includes(optionIndex)) {
                                    question.correct.push(optionIndex);
                                }

                                if (!event.target.checked) {
                                    question.correct = question.correct.filter(function(answer) {
                                        return answer !== optionIndex;
                                    });
                                }
                            } else {
                                question.correct = optionIndex;
                            }

                            renderNewExamQuestions();
                        }
                    });

                    newExamQuestions.addEventListener('click', function(event) {
                        const button = event.target.closest('button');

                        if (!button || !creatingExam) {
                            return;
                        }

                        if (button.dataset.focusNewQuestion !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.focusNewQuestion, 10);
                            const questionInput = newExamQuestions.querySelector(
                                '[data-new-question-text="' + questionIndex + '"]'
                            );

                            if (questionInput) {
                                questionInput.focus();
                            }

                            return;
                        }

                        if (button.dataset.duplicateNewQuestion !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.duplicateNewQuestion, 10);
                            const duplicatedQuestion = cloneExam(creatingExam.items[questionIndex]);

                            creatingExam.items.splice(questionIndex + 1, 0, duplicatedQuestion);
                            rebalanceNewExamPoints();
                            renderNewExamQuestions();

                            return;
                        }

                        if (button.dataset.removeNewQuestion !== undefined && creatingExam.items.length > 1) {
                            const questionIndex = Number.parseInt(button.dataset.removeNewQuestion, 10);

                            creatingExam.items.splice(questionIndex, 1);
                            rebalanceNewExamPoints();
                            renderNewExamQuestions();

                            return;
                        }

                        if (button.dataset.addNewQuestionOption !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.addNewQuestionOption, 10);
                            const optionIndex = creatingExam.items[questionIndex].options.length;

                            creatingExam.items[questionIndex].options.push('');
                            renderNewExamQuestions();

                            const optionInput = newExamQuestions.querySelector(
                                '[data-new-question-option="' + questionIndex + '"][data-option-index="' + optionIndex + '"]'
                            );

                            if (optionInput) {
                                optionInput.focus();
                            }

                            return;
                        }

                        if (button.dataset.removeNewQuestionOption !== undefined) {
                            const questionIndex = Number.parseInt(button.dataset.removeNewQuestionOption, 10);
                            const optionIndex = Number.parseInt(button.dataset.optionIndex, 10);
                            const question = creatingExam.items[questionIndex];

                            if (question.options.length <= 2 || question.type === 'boolean') {
                                return;
                            }

                            question.options.splice(optionIndex, 1);

                            if (question.type === 'multiple') {
                                question.correct = question.correct.filter(function(answer) {
                                    return answer !== optionIndex;
                                }).map(function(answer) {
                                    return answer > optionIndex ? answer - 1 : answer;
                                });

                                if (!question.correct.length) {
                                    question.correct = [0];
                                }
                            } else if (question.correct === optionIndex) {
                                question.correct = 0;
                            } else if (question.correct > optionIndex) {
                                question.correct -= 1;
                            }

                            renderNewExamQuestions();
                        }
                    });
                }

                if (newExamForm) {
                    newExamForm.addEventListener('submit', function(event) {
                        event.preventDefault();

                        if (!creatingExam) {
                            return;
                        }

                        const saveMode = event.submitter && event.submitter.value === 'published'
                            ? 'published'
                            : 'draft';

                        if (saveMode === 'published' && !newExamForm.reportValidity()) {
                            return;
                        }

                        if (saveMode === 'published') {
                            const questionWithoutAnswer = creatingExam.items.findIndex(function(question) {
                                return question.type === 'multiple' && !question.correct.length;
                            });

                            if (questionWithoutAnswer !== -1) {
                                const firstAnswer = newExamQuestions.querySelector(
                                    '[data-new-question-correct="' + questionWithoutAnswer + '"]'
                                );

                                if (firstAnswer) {
                                    firstAnswer.setCustomValidity('Selecciona al menos una respuesta correcta.');
                                    firstAnswer.reportValidity();
                                }

                                return;
                            }
                        }

                        creatingExam.id = createExamId();
                        creatingExam.module = newExamModule.value;
                        creatingExam.name = newExamForm.elements.name.value.trim() || 'Examen sin titulo';
                        creatingExam.instructions = newExamForm.elements.instructions.value.trim();
                        creatingExam.minimum = Number.parseInt(newExamForm.elements.minimum.value, 10);
                        creatingExam.duration = Number.parseInt(newExamForm.elements.duration.value, 10);
                        creatingExam.attempts = Number.parseInt(newExamForm.elements.attempts.value, 10);
                        creatingExam.randomize = newExamForm.elements.randomize.checked;
                        creatingExam.status = saveMode === 'published' ? 'Publicado' : 'Borrador';
                        creatingExam.tone = saveMode === 'published' ? 'active' : 'draft';
                        creatingExam.items = creatingExam.items.map(function(question) {
                            return Object.assign({}, question, {
                                text: question.text.trim(),
                                options: question.options.map(function(option) { return option.trim(); }),
                            });
                        });

                        const savedExam = normaliseExam(cloneExam(creatingExam));

                        examRecords[savedExam.id] = savedExam;
                        persistExams();
                        const savedRow = updateExamTableRow(savedExam);

                        closeNewExamBuilder();
                        activateProgramTab('exams');
                        selectProgram(savedExam.projectId, false);
                        showExamSaveFeedback(savedExam, saveMode);

                        if (savedRow) {
                            savedRow.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                        }
                    });
                }

                activateRole('admin');

                if (programSelector && programSelector.options.length) {
                    let selectedProgramId = programSelector.options[0].value;

                    try {
                        selectedProgramId = window.localStorage.getItem(selectedProgramStorageKey) || selectedProgramId;
                    } catch (error) {
                        // Use the first program when browser storage is unavailable.
                    }

                    selectProgram(selectedProgramId, false);
                }

                if (programButtons.length) {
                    activateProgramTab('modules');
                }
            });
        </script>
    @endpush
</x-admin-layout>
