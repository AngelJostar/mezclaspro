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
                    ['id' => 'induccion', 'name' => 'Induccion y seguridad operativa', 'progress' => 75],
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
                    ['id' => 'induccion', 'name' => 'Induccion y seguridad operativa', 'progress' => 40],
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
                    ['id' => 'citotoxicos', 'name' => 'Manejo seguro de citotoxicos', 'progress' => 20],
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
                    ['id' => 'proveedores', 'name' => 'Validacion de proveedores', 'progress' => 90],
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

        $programTabs = [
            ['id' => 'projects', 'label' => 'Programas', 'icon' => 'fa-regular fa-folder', 'active' => true],
            ['id' => 'modules', 'label' => 'Modulos', 'icon' => 'fa-solid fa-book-open'],
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
            ['label' => 'Inicio', 'icon' => 'fa-solid fa-house', 'active' => true],
            ['label' => 'Mi capacitacion', 'icon' => 'fa-solid fa-play'],
            ['label' => 'Tareas', 'icon' => 'fa-solid fa-list-check'],
            ['label' => 'Resultados', 'icon' => 'fa-solid fa-chart-line'],
            ['label' => 'Certificados', 'icon' => 'fa-solid fa-award'],
        ];

        $routeSteps = [
            ['title' => 'Bienvenida e induccion', 'meta' => '2 capitulos vistos', 'status' => 'Completado', 'progress' => 100],
            ['title' => 'Seguridad operativa', 'meta' => 'Capitulo activo', 'status' => 'En curso', 'progress' => 50],
            ['title' => 'Procesos esenciales', 'meta' => 'Examen pendiente', 'status' => 'Bloqueado', 'progress' => 0],
            ['title' => 'Cierre y certificado', 'meta' => 'Se desbloquea al aprobar', 'status' => 'Bloqueado', 'progress' => 0],
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
                <nav class="training-tabs is-programs" aria-label="Secciones de programas">
                    @foreach ($programTabs as $tab)
                        <button type="button" data-program-tab="{{ $tab['id'] }}"
                            class="{{ ! empty($tab['active']) ? 'is-active' : '' }}">
                            <i class="{{ $tab['icon'] }}"></i>
                            <span>{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="training-program-context" data-program-context hidden>
                    <label>
                        <span>Programa de capacitacion</span>
                        <span class="training-program-select-control">
                            <select data-program-selector aria-label="Seleccionar programa de capacitacion">
                                @foreach ($projects as $project)
                                    <option value="{{ $project['id'] }}">{{ $project['title'] }}</option>
                                @endforeach
                            </select>
                            <i class="fa-solid fa-chevron-down training-program-select-icon" aria-hidden="true"></i>
                        </span>
                    </label>
                </div>

                <div data-program-panel="projects">
                    <div class="training-section-heading">
                        <div>
                            <h2>Programas de capacitacion</h2>
                            <p>Programas activos y en preparacion.</p>
                        </div>

                        <button type="button" class="training-primary-button">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nuevo programa</span>
                        </button>
                    </div>

                    <div class="training-project-grid">
                        @foreach ($projects as $project)
                            <article class="training-project-card" data-program-card data-program-id="{{ $project['id'] }}">
                                <div class="training-project-card-header">
                                    <span class="training-project-icon"><i class="fa-regular fa-folder-open"></i></span>
                                    <span class="training-program-status is-{{ $project['tone'] }}" data-program-status>{{ $project['status'] }}</span>
                                </div>
                                <h3 data-program-title>{{ $project['title'] }}</h3>
                                <p data-program-description>{{ $project['description'] }}</p>
                                <dl>
                                    <div><dt>Responsable</dt><dd data-program-owner>{{ $project['owner'] }}</dd></div>
                                    <div><dt>Modulos</dt><dd>{{ $project['modules'] }}</dd></div>
                                    <div><dt>Alumnos</dt><dd>{{ $project['students'] }}</dd></div>
                                </dl>
                                <div class="training-card-actions">
                                    <button type="button" class="training-secondary-button">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <span>Abrir programa</span>
                                    </button>
                                    <button type="button" class="training-edit-button" data-edit-program>
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Editar programa</span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <dialog class="training-program-modal" data-program-modal aria-labelledby="training-program-modal-title">
                        <form data-program-form>
                            <header>
                                <div>
                                    <span>Programa de capacitacion</span>
                                    <h3 id="training-program-modal-title">Editar programa</h3>
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
                                    <span>Guardar cambios</span>
                                </button>
                            </footer>
                        </form>
                    </dialog>
                </div>

                <div data-program-panel="modules" hidden>
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

                        <div class="training-table training-program-table" role="region" aria-label="Examenes de programas" tabindex="0">
                            <table>
                                <thead>
                                    <tr>
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
                                            <td data-exam-module>{{ $exam['module'] }}</td>
                                            <td data-exam-question-count>{{ $exam['questions'] }}</td>
                                            <td data-exam-minimum>{{ $exam['minimum'] }}%</td>
                                            <td><span class="training-program-status is-{{ $exam['tone'] }}" data-exam-status>{{ $exam['status'] }}</span></td>
                                            <td>
                                                <button type="button" class="training-icon-button" data-edit-exam="{{ $exam['id'] }}"
                                                    title="Editar examen" aria-label="Editar examen de {{ $exam['module'] }}">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <button type="button" class="training-icon-button" data-view-exam="{{ $exam['id'] }}"
                                                    title="Ver examen" aria-label="Ver examen de {{ $exam['module'] }}">
                                                    <i class="fa-regular fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-program-empty-row hidden>
                                        <td colspan="6">Este programa aun no tiene examenes registrados.</td>
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
                                <th rowspan="2">Editar</th>
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
                            @endphp
                            <tr data-student-row data-student-name="{{ $person['name'] }}">
                                <td>
                                    <div class="training-person">
                                        <span class="training-avatar is-{{ $person['avatar'] }}">{{ $person['initials'] }}</span>
                                        <strong>{{ $person['name'] }}</strong>
                                    </div>
                                </td>
                                @if ($isPersonnelPage)
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
                                                <x-inline-user-credential-editor :user="$personUser" field="password" compact />
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
                                                <x-inline-user-credential-editor :user="$personUser" field="training_password" compact />
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
                                <td colspan="{{ $isPersonnelPage ? 19 : 7 }}" class="training-table-empty">
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
                @include('admin.capacitaciones.partials.personnel-user-management')
            @endif

            <dialog class="training-program-modal training-assignment-modal" data-training-assignment-modal
                aria-labelledby="training-assignment-title">
                <form data-training-assignment-form>
                    <header>
                        <div>
                            <span>Nueva capacitacion</span>
                            <h3 id="training-assignment-title">Seleccionar programa o modulos</h3>
                            <p>Alumno: <strong data-assignment-student></strong></p>
                        </div>
                        <button type="button" class="training-modal-close" data-close-training-assignment
                            title="Cerrar" aria-label="Cerrar seleccion de capacitacion">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="training-assignment-body">
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
            <nav class="training-tabs" aria-label="Secciones de usuario">
                @foreach ($learnerTabs as $tab)
                    <button type="button" class="{{ ! empty($tab['active']) ? 'is-active' : '' }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="training-learner-hero">
                <div>
                    <span>Panel del participante</span>
                    <h2>Buenos dias, Mariana</h2>
                    <p>Continua la ruta de Induccion y seguridad operativa.</p>
                </div>

                <button type="button" class="training-primary-button">
                    <i class="fa-solid fa-play"></i>
                    <span>Continuar capacitacion</span>
                </button>
            </div>

            <div class="training-learner-metrics">
                <article>
                    <span>Progreso general</span>
                    <strong>50%</strong>
                    <div class="training-progress-track">
                        <span style="width: 50%"></span>
                    </div>
                </article>
                <article>
                    <span>Modulos aprobados</span>
                    <strong>1/4</strong>
                </article>
                <article>
                    <span>Tareas abiertas</span>
                    <strong>3</strong>
                </article>
                <article>
                    <span>Ultima calificacion</span>
                    <strong>88%</strong>
                </article>
            </div>

            <div class="training-learner-grid">
                <article class="training-video-panel">
                    <div class="training-video-heading">
                        <div>
                            <span>Modulo 2</span>
                            <h3>Seguridad operativa</h3>
                        </div>
                        <b>50% del modulo</b>
                    </div>

                    <div class="training-video-frame" aria-label="Video de capacitacion">
                        <i class="fa-solid fa-play"></i>
                        <strong>Video de capacitacion</strong>
                    </div>

                    <div class="training-chapter-detail">
                        <div>
                            <span>Capitulo 1 de 2</span>
                            <h4>Buenas practicas en area esteril</h4>
                            <p>Revisa los puntos criticos antes de avanzar al examen del modulo.</p>
                        </div>
                        <button type="button" class="training-primary-button">Marcar capitulo y continuar</button>
                    </div>
                </article>

                <aside class="training-route-panel">
                    <div class="training-route-heading">
                        <span>Ruta de capacitacion</span>
                        <strong>Avanza por capitulos</strong>
                    </div>

                    @foreach ($routeSteps as $index => $step)
                        <button type="button" class="training-route-step {{ $step['progress'] === 100 ? 'is-done' : ($step['progress'] > 0 ? 'is-current' : '') }}">
                            <span>{{ $index + 1 }}</span>
                            <div>
                                <strong>{{ $step['title'] }}</strong>
                                <small>{{ $step['meta'] }}</small>
                            </div>
                            <em>{{ $step['status'] }}</em>
                        </button>
                    @endforeach
                </aside>
            </div>
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

            .training-tabs.is-programs {
                grid-template-columns: repeat(4, minmax(0, 1fr));
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

            .training-student-view-switch {
                display: inline-grid;
                grid-template-columns: repeat(2, minmax(8.5rem, 1fr));
                gap: 0.55rem;
                margin-top: 0.2rem;
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
            }

            @media (max-width: 900px) {
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
                const programContext = root.querySelector('[data-program-context]');
                const programSelector = root.querySelector('[data-program-selector]');
                const moduleItems = root.querySelectorAll('[data-module-item]');
                const moduleCount = root.querySelector('[data-module-count]');
                const chapterVideoInputs = root.querySelectorAll('[data-chapter-video]');
                const programModal = root.querySelector('[data-program-modal]');
                const programForm = root.querySelector('[data-program-form]');
                const trainingAssignmentModal = root.querySelector('[data-training-assignment-modal]');
                const trainingAssignmentForm = root.querySelector('[data-training-assignment-form]');
                const assignmentStudent = root.querySelector('[data-assignment-student]');
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
                const programStorageKey = 'mezclaspro.training.programs.v1';
                const selectedProgramStorageKey = 'mezclaspro.training.selected-program.v1';
                const examStorageKey = 'mezclaspro.training.exams.v1';
                const assignmentStorageKey = 'mezclaspro.training.assignments.v1';
                const employmentStatusStorageKey = 'mezclaspro.training.employment-statuses.v1';
                const examDefaults = @json(collect($exams)->keyBy('id')->all());
                const moduleDefaults = @json($modules);
                let examRecords = readStoredExams();
                let editingExam = null;
                let creatingExam = null;
                let assignmentTargetRow = null;

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

                function renderAssignmentsForRow(row, assignments) {
                    const currentPrograms = row ? row.querySelector('[data-current-programs]') : null;

                    if (!currentPrograms) {
                        return;
                    }

                    currentPrograms.querySelectorAll('[data-added-assignment]').forEach(function(program) {
                        program.remove();
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
                    });

                    const hasPrograms = currentPrograms.querySelector('[data-current-program-id]');
                    const emptyProgram = currentPrograms.querySelector('[data-empty-current-program]');

                    if (hasPrograms && emptyProgram) {
                        emptyProgram.remove();
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
                        assignmentConfirm.disabled = checkedModules === 0;
                    }
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

                    if (assignmentStudent) {
                        assignmentStudent.textContent = row.dataset.studentName;
                    }

                    trainingAssignmentModal.showModal();

                    if (assignmentSelectAll) {
                        assignmentSelectAll.focus();
                    }
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
                    button.setAttribute('aria-label', (isEdit ? 'Editar examen de ' : 'Ver examen de ') + exam.module);
                    button.appendChild(createExamElement('i', isEdit ? 'fa-solid fa-pen' : 'fa-regular fa-eye'));

                    return button;
                }

                function ensureExamTableRow(exam) {
                    const existingRow = findExamTableRow(exam.id);

                    if (existingRow || !examTableBody) {
                        return existingRow;
                    }

                    const row = createExamElement('tr');
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
                    moduleCell.dataset.examModule = '';
                    countCell.dataset.examQuestionCount = '';
                    minimumCell.dataset.examMinimum = '';
                    status.dataset.examStatus = '';
                    statusCell.appendChild(status);
                    editCell.appendChild(createExamActionButton('edit', exam));
                    viewCell.appendChild(createExamActionButton('view', exam));
                    row.append(moduleCell, countCell, minimumCell, statusCell, editCell, viewCell);
                    examTableBody.insertBefore(row, emptyRow || null);

                    return row;
                }

                function updateExamTableRow(exam) {
                    const row = ensureExamTableRow(exam) || Array.from(root.querySelectorAll('[data-exam-id]')).find(function(item) {
                        return item.dataset.examId === exam.id;
                    });

                    if (!row) {
                        return;
                    }

                    row.querySelector('[data-exam-module]').textContent = exam.module;
                    row.querySelector('[data-exam-question-count]').textContent = exam.items.length;
                    row.querySelector('[data-exam-minimum]').textContent = exam.minimum + '%';

                    const status = row.querySelector('[data-exam-status]');

                    status.textContent = exam.status;
                    status.classList.remove('is-active', 'is-draft', 'is-inactive');
                    status.classList.add('is-' + exam.tone);

                    const editButton = row.querySelector('[data-edit-exam]');
                    const viewButton = row.querySelector('[data-view-exam]');

                    editButton.setAttribute('aria-label', 'Editar examen de ' + exam.module);
                    viewButton.setAttribute('aria-label', 'Ver examen de ' + exam.module);
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

                    if (programContext) {
                        programContext.hidden = true;
                    }

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

                    if (programContext) {
                        const activeProgramTab = root.querySelector('[data-program-tab].is-active');

                        programContext.hidden = !activeProgramTab || activeProgramTab.dataset.programTab === 'projects';
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
                        renderAssignmentsForRow(assignmentTargetRow, mergedAssignments);
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

                    if (programContext) {
                        programContext.hidden = tab === 'projects' || (tab === 'exams' && examCreateView && !examCreateView.hidden);
                    }
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

                root.querySelectorAll('[data-program-card]').forEach(function(card) {
                    const savedProgram = storedPrograms[card.dataset.programId];

                    if (savedProgram) {
                        applyProgramData(card, savedProgram);
                    }
                });

                root.querySelectorAll('[data-edit-program]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const card = button.closest('[data-program-card]');

                        if (!card || !programModal || !programForm) {
                            return;
                        }

                        programForm.elements.program_id.value = card.dataset.programId;
                        programForm.elements.title.value = card.querySelector('[data-program-title]').textContent.trim();
                        programForm.elements.description.value = card.querySelector('[data-program-description]').textContent.trim();
                        programForm.elements.owner.value = card.querySelector('[data-program-owner]').textContent.trim();
                        programForm.elements.status.value = card.querySelector('[data-program-status]').textContent.trim();

                        programModal.showModal();
                        programForm.elements.title.focus();
                    });
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

                        const programId = programForm.elements.program_id.value;
                        const card = root.querySelector('[data-program-card][data-program-id="' + programId + '"]');
                        const programData = {
                            title: programForm.elements.title.value.trim(),
                            description: programForm.elements.description.value.trim(),
                            owner: programForm.elements.owner.value.trim(),
                            status: programForm.elements.status.value,
                        };

                        applyProgramData(card, programData);

                        const programs = readStoredPrograms();
                        programs[programId] = programData;

                        try {
                            window.localStorage.setItem(programStorageKey, JSON.stringify(programs));
                        } catch (error) {
                            // The card remains updated even when browser storage is unavailable.
                        }

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
                        updateExamTableRow(savedExam);
                        closeNewExamBuilder();
                        filterProgramRows('exams', programSelector.value);
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
                    activateProgramTab('projects');
                }
            });
        </script>
    @endpush
</x-admin-layout>
