<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Oncologicos\MezclaController;
use App\Models\Oncologicos\MezclaMedicamento;
use App\Services\MedicineRemainderService;
use App\Services\OncologicMedicationInventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\RemainderInventory;
use Tests\TestCase;

class CombinedDispensingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RemainderInventory::seed();
        DB::table('medicine_remainders')->delete(); // This connection is SQLite :memory: only.
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('medicine_list_presentation', function (Blueprint $table) {
            $table->integer('medicine_presentation_id');
            $table->integer('medicine_list_id');
            $table->decimal('precio')->default(10);
        });
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->decimal('stock_ml_actual', 12, 4)->nullable();
            $table->timestamps();
        });
        Schema::table('medicine_remainders', function (Blueprint $table) {
            foreach (['nutrition_medicine_presentation_id', 'medicine_laboratory_stock_id', 'lote', 'caducidad',
                'opened_at', 'initial_ml', 'opened_for_type', 'opened_for_id', 'discarded_at', 'discard_reason', 'notes'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('medicine_remainder_movements', function (Blueprint $table) {
            $table->id();
            foreach (['medicine_remainder_id', 'user_id', 'movement_type', 'quantity_ml', 'stock_before_ml',
                'stock_after_ml', 'reference_type', 'reference_id', 'notes'] as $column) $table->string($column)->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_03_05_232105_create_medicine_batch_movements_table.php'))->up();
        Schema::table('medicine_batch_movements', function (Blueprint $table) {
            $table->integer('warehouse_id')->nullable();
            $table->decimal('quantity_ml', 12, 4);
            $table->decimal('stock_ml_before', 12, 4);
            $table->decimal('stock_ml_after', 12, 4);
        });
        foreach ([10 => 100, 12 => 400] as $id => $mg) {
            DB::table('medicine_presentations')->where('id', $id)->update([
                'cantidad_medicamento' => $mg, 'volumen_diluyente' => $mg / 20, 'stability_hours' => 24,
            ]);
            DB::table('medicine_list_presentation')->insert(['medicine_presentation_id' => $id, 'medicine_list_id' => 1]);
        }
        DB::table('medicine_batches')->where('id', 100)->update(['stock_actual' => 2, 'stock_ml_actual' => 10]);
        DB::table('medicine_batches')->insert([
            'id' => 120, 'medicine_presentation_id' => 12, 'laboratory_id' => 1, 'warehouse_id' => 1,
            'lote' => 'LOTE-400', 'caducidad' => now()->addYear()->toDateString(),
            'stock_actual' => 3, 'stock_reservado' => 0, 'stock_ml_actual' => 60,
        ]);
    }

    private function selection(int $small = 2, int $large = 2): array
    {
        return [['batch_id' => 100, 'frascos' => $small], ['batch_id' => 120, 'frascos' => $large]];
    }

    public function test_combined_dose_consumes_larger_presentation_first_and_does_not_require_ten_small_vials(): void
    {
        $result = app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 1000);
        $this->assertSame([120, 100], array_column($result, 'batch_id'));
        $this->assertSame([2, 2], array_column($result, 'opened_containers'));
        $this->assertEquals(1000, array_sum(array_column($result, 'dose_provided_mg')));
        $this->assertEquals(50, array_sum(array_column($result, 'used_ml')));
        $this->assertEquals(0, DB::table('medicine_batches')->where('id', 100)->value('stock_actual'));
        $this->assertEquals(1, DB::table('medicine_batches')->where('id', 120)->value('stock_actual'));
        $this->assertDatabaseCount('medicine_remainders', 0);
        $this->assertDatabaseCount('medicine_batch_movements', 2);
    }

    public function test_controller_records_both_presentations_and_their_actual_volumes(): void
    {
        Schema::create('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            $table->id();
            foreach (['mezcla_medicamento_id', 'medicine_batch_id', 'unidades_usadas', 'unidades_abiertas',
                'volumen_usado_ml', 'charge_by_snapshot', 'presentacion_snapshot', 'cantidad_medicamento_snapshot',
                'volumen_diluyente_snapshot', 'legend_snapshot', 'lote_usado', 'caducidad_usada',
                'precio_frasco_snapshot', 'precio_unitario_snapshot', 'subtotal'] as $column) $table->string($column)->nullable();
            $table->timestamps();
        });
        $medicine = new MezclaMedicamento();
        $medicine->setRawAttributes(['id' => 1, 'dosis' => 1000, 'charge_by' => 'frasco']);
        $method = new \ReflectionMethod(MezclaController::class, 'attachPresentacionesAndConsumeInventory');
        $result = $method->invoke(app(MezclaController::class), $medicine, $this->selection(), 10, 1, 1, 38);
        $this->assertEquals(50, $result['dosis_ml']);
        $this->assertDatabaseHas('mezcla_medicamento_presentaciones', ['medicine_batch_id' => 120, 'unidades_abiertas' => 2, 'volumen_usado_ml' => 40]);
        $this->assertDatabaseHas('mezcla_medicamento_presentaciones', ['medicine_batch_id' => 100, 'unidades_abiertas' => 2, 'volumen_usado_ml' => 10]);
    }

    public function test_excess_is_recorded_only_for_the_unused_part_of_the_last_vial(): void
    {
        $result = app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 950);
        $this->assertEquals(950, array_sum(array_column($result, 'dose_provided_mg')));
        $this->assertDatabaseHas('medicine_remainders', ['medicine_batch_id' => 100, 'current_ml' => 2.5, 'opened_for_id' => 38]);
        $this->assertDatabaseCount('medicine_remainders', 1);
    }

    public function test_smaller_presentation_remainder_is_used_without_opening_its_vials(): void
    {
        app(MedicineRemainderService::class)->openContainer([
            'domain' => 'oncologico', 'laboratory_id' => 1, 'warehouse_id' => 1,
            'medicine_presentation_id' => 10, 'medicine_batch_id' => 100, 'stability_hours' => 24,
        ], 5, 3.5);
        $result = app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(0, 1), 1, 1, 10, 38, 430);
        $this->assertSame([1, 0], array_column($result, 'opened_containers'));
        $this->assertEquals(430, array_sum(array_column($result, 'dose_provided_mg')));
        $this->assertEquals(2, DB::table('medicine_batches')->where('id', 100)->value('stock_actual'));
    }

    public function test_invalid_or_incomplete_selections_never_change_inventory(): void
    {
        foreach ([$this->selection(1, 2), $this->selection(3, 2), $this->selection(-1, 2),
            [['batch_id' => 100, 'frascos' => 1.5]], [['batch_id' => 100, 'frascos' => 2], ['batch_id' => 100, 'frascos' => 2]]] as $selection) {
            try {
                app(OncologicMedicationInventoryService::class)->consumeSelection($selection, 1, 1, 10, 38, 1000);
                $this->fail('Invalid selection was accepted.');
            } catch (\RuntimeException|\InvalidArgumentException $e) {
                $this->assertDatabaseCount('medicine_batch_movements', 0);
                $this->assertDatabaseCount('medicine_remainders', 0);
                $this->assertEquals(3, DB::table('medicine_batches')->where('id', 120)->value('stock_actual'));
            }
        }
    }

    public function test_different_brands_and_unavailable_lots_are_rejected_server_side(): void
    {
        foreach ([['marca' => 'Otra marca'], ['is_available' => 0], ['catalog_id' => 20]] as $change) {
            DB::table('medicine_presentations')->where('id', 12)->update($change);
            try {
                app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 1000);
                $this->fail('Unavailable presentation was accepted.');
            } catch (\RuntimeException $e) {
                $this->assertDatabaseCount('medicine_batch_movements', 0);
            }
            DB::table('medicine_presentations')->where('id', 12)->update(['marca' => 'Marca de prueba', 'is_available' => 1, 'catalog_id' => 10]);
        }
        DB::table('medicine_batches')->where('id', 120)->update(['stock_reservado' => 2]);
        $this->expectException(\RuntimeException::class);
        app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 1000);
    }

    public function test_expired_foreign_central_and_unlisted_batches_cannot_be_selected(): void
    {
        foreach ([['caducidad' => now()->subDay()->toDateString()], ['laboratory_id' => 2]] as $change) {
            DB::table('medicine_batches')->where('id', 120)->update($change);
            try {
                app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 1000);
                $this->fail('Invalid lot was accepted.');
            } catch (\RuntimeException $e) {
                $this->assertDatabaseCount('medicine_batch_movements', 0);
            }
            DB::table('medicine_batches')->where('id', 120)->update(['caducidad' => now()->addYear()->toDateString(), 'laboratory_id' => 1]);
        }
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 12)->delete();
        $this->expectException(\RuntimeException::class);
        app(OncologicMedicationInventoryService::class)->consumeSelection($this->selection(), 1, 1, 10, 38, 1000);
    }

    public function test_failure_in_a_later_presentation_rolls_back_the_already_consumed_lot(): void
    {
        $service = \Mockery::mock(OncologicMedicationInventoryService::class)->makePartial();
        $service->shouldReceive('consume')->withArgs(fn (...$args) => $args[0] === 120)->once()->passthru();
        $service->shouldReceive('consume')->withArgs(fn (...$args) => $args[0] === 100)->once()
            ->andThrow(new \RuntimeException('Fallo simulado al guardar el segundo lote.'));
        try {
            $service->consumeSelection($this->selection(), 1, 1, 10, 38, 950);
            $this->fail('Expected failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado al guardar el segundo lote.', $e->getMessage());
            $this->assertEquals(3, DB::table('medicine_batches')->where('id', 120)->value('stock_actual'));
            $this->assertDatabaseCount('medicine_batch_movements', 0);
            $this->assertDatabaseCount('medicine_remainders', 0);
        }
    }
}
