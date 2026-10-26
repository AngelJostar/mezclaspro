<?php

namespace App\Support;

class TrainingStudentDefaults
{
    /** Existing student records kept with stable keys during the transition from the legacy view. */
    public static function records(): array
    {
        $records = [
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
        return array_map(fn ($record) => $record + ['studentKey' => 'preview-'.\Illuminate\Support\Str::slug($record['name'])], $records);
    }
}

