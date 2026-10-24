<?php

namespace App\Support;

// Content previously displayed in the training screens, retained during migration.
class TrainingContentDefaults
{
    public static function chapters(): array {
        return [
            ['title' => 'Objetivo del programa', 'duration' => '12 min', 'objective' => 'Conocer la ruta, los requisitos y el minimo de aprobacion.', 'video' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE'],
            ['title' => 'Como avanzar entre modulos', 'duration' => '9 min', 'objective' => 'Entender los bloqueos, examenes y certificados.', 'video' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE'],
        ];
    }

    public static function exams(): array {
        return [
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
    }

    public static function programTasks(): array {
        return [
            ['projectId' => 'induccion', 'title' => 'Checklist de seguridad del area', 'project' => 'Induccion y seguridad operativa', 'stage' => 'Modulo 2', 'due' => '22 ago 2026', 'status' => 'Activa', 'tone' => 'active'],
            ['projectId' => 'induccion', 'title' => 'Subir evidencia de equipo de proteccion', 'project' => 'Induccion y seguridad operativa', 'stage' => 'Modulo 2', 'due' => '25 ago 2026', 'status' => 'Activa', 'tone' => 'active'],
            ['projectId' => 'almacenamiento', 'title' => 'Confirmar lectura del procedimiento', 'project' => 'Buenas practicas de almacenamiento', 'stage' => 'Modulo 1', 'due' => 'Sin fecha', 'status' => 'Borrador', 'tone' => 'draft'],
        ];
    }
}
