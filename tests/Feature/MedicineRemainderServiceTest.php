<?php

namespace Tests\Feature;

use App\Models\MedicineRemainder;
use App\Models\MedicineRemainderMovement;
use App\Services\MedicineRemainderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MedicineRemainderServiceTest extends TestCase
{
    use RefreshDatabase;

    private MedicineRemainderService $service;
    private int $laboratoryId;
    private int $presentationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MedicineRemainderService::class);
        $this->laboratoryId = DB::table('laboratories')->insertGetId([
            'nombre' => 'Laboratorio de pruebas',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $catalogId = DB::table('medicines_catalog')->insertGetId([
            'denominacion' => 'Medicamento de pruebas',
            'denominacion_comercial' => 'Marca de pruebas',
            'state' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->presentationId = DB::table('medicine_presentations')->insertGetId([
            'catalog_id' => $catalogId,
            'presentacion' => 'Frasco 100 mg/10 mL',
            'contenido_valor' => 100,
            'contenido_unidad' => 'mg',
            'cantidad_medicamento' => 100,
            'volumen_diluyente' => 10,
            'is_available' => true,
            'stability_hours' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_opening_a_container_creates_a_traceable_remainder(): void
    {
        $openedAt = now()->startOfSecond();
        $remainder = $this->openRemainder(10, 4, 24, 100, $openedAt);

        $this->assertNotNull($remainder);
        $this->assertSame('6.0000', $remainder->initial_ml);
        $this->assertSame('6.0000', $remainder->current_ml);
        $this->assertTrue($remainder->usable_until->equalTo($openedAt->copy()->addHours(24)));
        $this->assertDatabaseHas('medicine_remainder_movements', [
            'medicine_remainder_id' => $remainder->id,
            'movement_type' => 'apertura',
            'reference_type' => 'mezcla',
            'reference_id' => 100,
        ]);
    }

    public function test_it_does_not_create_an_unsafe_or_empty_remainder(): void
    {
        $this->assertNull($this->openRemainder(10, 10, 24, 101));
        $this->assertNull($this->openRemainder(10, 4, 0, 102));
        $this->assertDatabaseCount('medicine_remainders', 0);
    }

    public function test_it_consumes_the_remainder_that_expires_first(): void
    {
        $later = $this->openRemainder(10, 2, 48, 110);
        $sooner = $this->openRemainder(10, 4, 12, 111);

        $result = $this->service->consumeAvailable(
            'oncologico',
            $this->presentationId,
            $this->laboratoryId,
            7,
            'mezcla',
            200
        );

        $this->assertSame(7.0, $result['consumed_ml']);
        $this->assertSame(0.0, $result['remaining_ml']);
        $this->assertSame($sooner->id, $result['allocations'][0]['remainder_id']);
        $this->assertSame($later->id, $result['allocations'][1]['remainder_id']);
        $this->assertDatabaseHas('medicine_remainders', ['id' => $sooner->id, 'current_ml' => 0]);
        $this->assertDatabaseHas('medicine_remainders', ['id' => $later->id, 'current_ml' => 7]);
    }

    public function test_expired_remainders_are_discarded_and_not_suggested(): void
    {
        $expired = $this->openRemainder(10, 3, 1, 120, now()->subHours(2));

        $available = $this->service->available(
            'oncologico',
            $this->presentationId,
            $this->laboratoryId
        );

        $this->assertTrue($available->isEmpty());
        $expired->refresh();
        $this->assertFalse($expired->is_active);
        $this->assertSame('0.0000', $expired->current_ml);
        $this->assertSame('Estabilidad vencida', $expired->discard_reason);
    }

    public function test_rollback_restores_consumed_remainder_only_once(): void
    {
        $remainder = $this->openRemainder(10, 2, 24, 130);
        $this->service->consumeAvailable(
            'oncologico',
            $this->presentationId,
            $this->laboratoryId,
            3,
            'mezcla',
            300
        );

        $this->service->rollbackReference('mezcla', 300);
        $remainder->refresh();
        $this->assertSame('8.0000', $remainder->current_ml);

        $this->service->rollbackReference('mezcla', 300);
        $remainder->refresh();
        $this->assertSame('8.0000', $remainder->current_ml);
        $this->assertSame(1, MedicineRemainderMovement::query()
            ->where('medicine_remainder_id', $remainder->id)
            ->where('movement_type', 'devolucion')
            ->count());
    }

    private function openRemainder(
        float $containerMl,
        float $usedMl,
        int $stabilityHours,
        int $referenceId,
        $openedAt = null
    ): ?MedicineRemainder {
        return $this->service->openContainer([
            'domain' => 'oncologico',
            'laboratory_id' => $this->laboratoryId,
            'medicine_presentation_id' => $this->presentationId,
            'lote' => 'LOTE-PRUEBA',
            'caducidad' => now()->addYear()->toDateString(),
            'opened_at' => $openedAt ?? now(),
            'stability_hours' => $stabilityHours,
            'reference_type' => 'mezcla',
            'reference_id' => $referenceId,
        ], $containerMl, $usedMl);
    }
}
