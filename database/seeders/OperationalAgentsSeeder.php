<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use App\Services\Agents\OperationalAgents;
use Illuminate\Database\Seeder;

class OperationalAgentsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            OperationalAgents::CONCILIATION => ['name' => 'Envío automático de conciliaciones',
                'description' => 'Crea y envía un periodo independiente por hospital, da seguimiento a sus respuestas y notifica por correo a hospital e institución.',
                'instructions' => 'Usar únicamente hospitales seleccionados y remisiones del periodo anterior. No crear periodos vacíos ni volver a enviar remisiones asignadas o enviadas. Reportar respuestas y errores por hospital.',
                'configuration' => OperationalAgents::defaults()],
            OperationalAgents::NUTRITION => ['name' => 'Comparativo de nutriciones por paciente',
                'description' => 'Compara peso, nutrientes prescritos y concentraciones de las nutriciones entregadas al paciente, por fecha. Genera un informe y un PDF con el detalle de cada nutrición.',
                'instructions' => 'Identificar al paciente por hospital y expediente, o por nombre completo y fecha de nacimiento. Mostrar todas las nutriciones entregadas en el rango y sus unidades. Describir cambios registrados sin inferir administración al paciente ni modificar prescripciones.',
                'configuration' => ['activation' => 'manual']],
        ] as $key => $data) {
            if (AiAgent::where('integration_key', $key)->exists()) continue;
            AiAgent::forceCreate($data + ['integration_key' => $key, 'is_active' => false]);
        }
    }
}
