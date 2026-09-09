<?php

namespace Tests\Feature;

use App\Services\MedicineRemainderService;
use App\Services\OncologicMedicationInventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\RemainderInventory;
use Tests\TestCase;

class DispensingProposalTest extends TestCase
{
    public function test_preview_pools_only_valid_remainders_in_the_same_presentation_and_warehouse_without_writes(): void
    {
        RemainderInventory::seed();
        foreach ([[2, 1], [1, 2]] as [$laboratoryId, $warehouseId]) {
            DB::table('medicine_remainders')->insert([
                'domain' => 'oncologico', 'medicine_presentation_id' => 10, 'medicine_batch_id' => 500,
                'laboratory_id' => $laboratoryId, 'warehouse_id' => $warehouseId,
                'current_ml' => 100, 'is_active' => 1, 'usable_until' => now()->addDay(),
            ]);
        }
        $before = DB::table('medicine_remainders')->get()->toJson();
        $pools = app(MedicineRemainderService::class)->availableOncologicTotals(1, [10]);
        $this->assertCount(2, $pools);
        $this->assertEqualsWithDelta(2.6234, $pools->firstWhere('warehouse_id', 1)->available_ml, 0.000001);
        $this->assertEquals(100, $pools->firstWhere('warehouse_id', 2)->available_ml);
        $this->assertSame($before, DB::table('medicine_remainders')->get()->toJson());
    }

    public function test_remainder_only_dispensing_does_not_switch_to_a_backup_lot_or_open_vials(): void
    {
        RemainderInventory::seed();
        Schema::create('medicine_list_presentation', function (Blueprint $table) {
            $table->integer('medicine_presentation_id');
            $table->integer('medicine_list_id');
            $table->decimal('precio');
        });
        DB::table('medicine_list_presentation')->insert(['medicine_presentation_id' => 10, 'medicine_list_id' => 1, 'precio' => 10]);
        DB::table('medicine_batches')->where('id', 100)->update(['stock_actual' => 0]);
        $this->mock(MedicineRemainderService::class)->shouldReceive('consumeAvailable')
            ->once()->with('oncologico', 10, 1, 2.0, 'mezcla', 1, 1, null)
            ->andReturn(['remaining_ml' => 0.0, 'consumed_ml' => 2.0, 'allocations' => []]);

        $result = app(OncologicMedicationInventoryService::class)->consume(100, 0, 1, 1, 10, 1, 50);
        $this->assertSame(100, $result->batch_id);
        $this->assertSame(0, $result->opened_containers);
        $this->assertSame(50.0, $result->dose_provided_mg);
        $this->assertEquals(0, DB::table('medicine_batches')->where('id', 100)->value('stock_actual'));
    }
}
