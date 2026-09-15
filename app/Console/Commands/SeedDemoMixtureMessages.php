<?php

namespace App\Console\Commands;

use App\Models\MixtureMessage;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use App\Services\MixtureMessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedDemoMixtureMessages extends Command
{
    protected $signature = 'demo:mixture-messages {mixtures* : IDs de mezclas para la demostracion}
        {--hospital-user= : Usuario hospital autorizado}
        {--central-user= : Usuario de Prodifem autorizado}';

    protected $description = 'Agrega conversaciones ficticias DEMO, sin modificar mezclas ni conversaciones existentes.';

    public function handle(MixtureMessagingService $messaging): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Los mensajes de demostracion solo se permiten en entornos locales o de pruebas.');
            return self::FAILURE;
        }

        $ids = collect($this->argument('mixtures'))->unique()->values();
        if ($ids->count() > 5 || $ids->contains(fn ($id) => ! ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('Selecciona de una a cinco mezclas validas.');
            return self::FAILURE;
        }
        $hospital = User::find($this->option('hospital-user'));
        $central = User::find($this->option('central-user'));
        if (! $hospital?->is_active || ! $central?->is_active
            || ! $hospital->hasAnyRole(['Cliente', 'Institucion'])
            || ! $central->hasAnyRole(['Admin', 'Super Admin'])
            || $messaging->side($central) !== 'central'
            || $hospital->isBlockedByOrganization()) {
            $this->error('Selecciona usuarios activos y autorizados del hospital y de Prodifem.');
            return self::FAILURE;
        }

        $mixtures = Mezcla::with('solicitud')->whereKey($ids->all())->get()->keyBy('id');
        $targets = collect();
        foreach ($ids as $id) {
            $mixture = $mixtures->get($id);
            $key = $mixture?->solicitud?->tipo_solicitud.':'.$id;
            $target = $messaging->targets($hospital, [$key])->get($key);
            if (! $target || ! $messaging->targets($central, [$key])->has($key)) {
                $this->error('La mezcla #'.$id.' no esta disponible para ambos usuarios. No se agregaron mensajes.');
                return self::FAILURE;
            }
            $targets->push($target);
        }

        $conversations = [
            [
                ['hospital', 'Hola, este mensaje ficticio permite probar la respuesta de Prodifem. Confirmen su recepcion, por favor.'],
            ],
            [
                ['hospital', 'Prueba de conversacion: solicitamos una actualizacion de esta solicitud.'],
                ['central', 'Estamos revisando la consulta de prueba. Esto no modifica la programacion real.'],
                ['hospital', 'Recibido. Dejamos esta consulta pendiente para probar la siguiente respuesta.'],
            ],
            [
                ['hospital', 'Solicito confirmar la recepcion de este mensaje de prueba.'],
                ['central', 'Confirmamos unicamente la recepcion de la prueba de mensajeria.'],
                ['hospital', 'Gracias. El historial de prueba se visualiza correctamente.'],
                ['central', 'Conversacion ficticia registrada. No implica aprobacion ni cambios en la mezcla.'],
            ],
        ];

        $counts = DB::transaction(function () use ($targets, $conversations, $hospital, $central, $messaging) {
            $created = 0;
            $threads = 0;
            foreach ($targets as $index => $target) {
                $target['model']->newQuery()->whereKey($target['id'])->lockForUpdate()->firstOrFail();
                // Never add demo content to an existing conversation, including a previous demo run.
                if ($messaging->messages($target)->exists()) continue;
                $conversation = $conversations[$index % count($conversations)];
                foreach ($conversation as $position => [$side, $body]) {
                    MixtureMessage::create([
                        'kind' => $target['kind'], 'target_id' => $target['id'], 'hospital_id' => $target['hospital_id'],
                        'author_id' => $side === 'hospital' ? $hospital->id : $central->id,
                        'author_name' => $side === 'hospital' ? 'Hospital (DEMO)' : 'Prodifem (DEMO)',
                        'sender_side' => $side, 'body' => '[DEMO] '.$body, 'client_token' => (string) Str::uuid(),
                        'created_at' => now()->subMinutes(count($conversation) - $position),
                    ]);
                    $created++;
                }
                $threads++;
            }
            return [$created, $threads];
        });

        $this->info($counts[0].' mensajes DEMO creados en '.$counts[1].' mezclas. Las conversaciones existentes no se modificaron.');
        return self::SUCCESS;
    }
}
