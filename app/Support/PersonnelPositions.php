<?php

namespace App\Support;

use App\Models\PersonnelProfile;

class PersonnelPositions
{
    public static function catalog(): array
    {
        return [
            'Operacion tecnica' => ['Responsable sanitario', 'Auxiliar de responsable sanitario', 'Coordinador de produccion',
                'Preparador de mezclas', 'Verificador', 'Auxiliar tecnico'],
            'Calidad' => ['Garantia de calidad', 'Control microbiologico', 'Control documental'],
            'Logistica y administracion' => ['Responsable de almacen', 'Auxiliar de almacen', PersonnelProfile::POSITION_COURIER, 'Capturista administrativo'],
            'Ventas' => [PersonnelProfile::POSITION_SELLER, PersonnelProfile::POSITION_SALES_SUPPORT, PersonnelProfile::POSITION_MOBILE],
        ];
    }
}
