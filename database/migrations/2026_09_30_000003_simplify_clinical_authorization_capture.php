<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $previous = 'No basta aceptar el aviso: antes del envio el usuario debe registrar nombre del medico, cedula profesional, justificacion, fecha y referencia de la autorizacion.';
        $replacement = 'Antes del envio el usuario debe registrar unicamente el nombre del medico que autoriza y su cedula profesional. No solicites folio, referencia, fecha, hora, justificacion ni una casilla adicional de confirmacion de autorizacion. El sistema registra automaticamente usuario y momento de captura, no la fecha de autorizacion del medico. Esta captura no sustituye la autorizacion medica ni verifica la identidad o cedula.';
        foreach (DB::table('ai_agents')->where('integration_key', 'clinical_support')->get(['id', 'instructions']) as $agent) {
            $instructions = str_replace($previous, $replacement, $agent->instructions ?? '');
            if ($instructions === ($agent->instructions ?? '')) continue;
            DB::table('ai_agents')->where('id', $agent->id)->update(['instructions' => $instructions, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Retain historical authorization records and subsequent edits to agent instructions.
    }
};
