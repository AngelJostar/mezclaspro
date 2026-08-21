<?php

namespace Tests\Feature;

use App\Models\ExternalMixtureRequest;
use App\Models\Hospital;
use App\Models\User;
use App\Services\Integrations\DrSam\ExternalMixtureNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExternalMixtureNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_only_an_authorized_user_and_keeps_the_event_unread(): void
    {
        $hospital = Hospital::query()->create([
            'external_code' => 'HOSP-NOTIFY-1',
            'name' => 'Hospital Notificaciones',
            'adress' => 'Dirección',
            'is_active' => true,
        ]);
        $authorized = User::query()->create([
            'name' => 'Operador', 'lastname' => 'Autorizado', 'username' => 'operator.notify',
            'password' => bcrypt('secret'), 'is_active' => true, 'hospital_id' => $hospital->id,
        ]);
        $unauthorized = User::query()->create([
            'name' => 'Operador', 'lastname' => 'Sin permiso', 'username' => 'operator.no-notify',
            'password' => bcrypt('secret'), 'is_active' => true, 'hospital_id' => $hospital->id,
        ]);
        $permission = Permission::findOrCreate('nutricionales_solicitudes_index', 'web');
        $authorized->givePermissionTo($permission);
        $external = ExternalMixtureRequest::query()->create([
            'remote_request_id' => (string) Str::uuid(),
            'local_external_id' => (string) Str::uuid(),
            'hospital_id' => $hospital->id,
            'catalog_type' => 'npt',
            'status' => 'materialization_failed',
            'payload_hash' => str_repeat('f', 64),
            'payload' => ['external_id' => 'NPT-ALERTA-1'],
            'last_error' => 'No hay existencia de Bolsa EVA.',
            'received_at' => now(),
        ]);

        app(ExternalMixtureNotificationService::class)->notify($external);

        $this->assertCount(1, $authorized->fresh()->unreadNotifications);
        $this->assertSame('Integración requiere atención', $authorized->fresh()->unreadNotifications->first()->data['title']);
        $this->assertStringContainsString('Bolsa EVA', $authorized->fresh()->unreadNotifications->first()->data['message']);
        $this->assertCount(0, $unauthorized->fresh()->notifications);
    }
}
