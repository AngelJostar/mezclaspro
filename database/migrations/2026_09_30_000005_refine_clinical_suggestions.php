<?php

use App\Services\Clinical\ClinicalEvidence;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public const PREVIOUS_POLICY = <<<'PROMPT'
Sugerencias para corregir parametros:
En cada advertencia o rechazo agrega una Sugerencia separada de la causa: identifica el campo por su nombre visible, el dato a verificar, la accion concreta y la comprobacion necesaria antes de volver a validar. Para un valor o rango clinico/quimico propuesto exige una fuente vigente revisada aplicable, cita su identificador y seccion, e indica unidades y supuestos. No inventes dosis, limites, conversiones ni valores de correccion ante evidencia ausente o contradictoria; pide al profesional confirmar el parametro y la fuente faltante. Para errores aritmeticos muestra los valores capturados, la diferencia y la condicion de consistencia, sin convertirla en una prescripcion. Si la suma de componentes supera el volumen total, verifica ambos contra la orden medica: el area medica debe confirmar un volumen final que contenga los componentes o revisar la formulacion si debe conservarse el volumen prescrito. No indiques reducir dosis proporcionalmente ni agregar agua para resolver ese exceso. Ninguna sugerencia se aplica automaticamente ni habilita el envio; el usuario captura la correccion confirmada y vuelve a validar. Corregir una inconsistencia no demuestra seguridad clinica, compatibilidad ni estabilidad.
PROMPT;

    public function up(): void
    {
        foreach (DB::table('ai_agents')->where('integration_key', ClinicalEvidence::KEY)->get(['id', 'instructions']) as $agent) {
            $instructions = $agent->instructions ?? '';
            if (str_contains($instructions, self::PREVIOUS_POLICY)) {
                $instructions = str_replace(self::PREVIOUS_POLICY, ClinicalEvidence::SUGGESTION_POLICY, $instructions);
            } elseif (!str_contains($instructions, ClinicalEvidence::SUGGESTION_POLICY)) {
                $instructions = trim($instructions."\n\n".ClinicalEvidence::SUGGESTION_POLICY);
            }
            if ($instructions === ($agent->instructions ?? '')) continue;
            DB::table('ai_agents')->where('id', $agent->id)->update(['instructions' => $instructions, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Keep subsequent profile edits and historical clinical review records intact.
    }
};
