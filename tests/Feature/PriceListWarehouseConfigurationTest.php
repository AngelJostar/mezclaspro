<?php

namespace Tests\Feature;

use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use App\Services\PriceListWarehouseConfigurationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PDO;
use Tests\TestCase;

class PriceListWarehouseConfigurationTest extends TestCase
{
    private Laboratory $laboratory;

    private Warehouse $mainWarehouse;

    private Warehouse $backupWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extension pdo_sqlite es necesaria para estas pruebas.');
        }

        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('estado')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id');
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->laboratory = Laboratory::query()->create([
            'nombre' => 'CDMX',
            'estado' => 'Ciudad de Mexico',
            'activo' => true,
        ]);
        $this->mainWarehouse = Warehouse::query()->create([
            'laboratory_id' => $this->laboratory->id,
            'name' => 'Almacen Central',
            'is_active' => true,
        ]);
        $this->backupWarehouse = Warehouse::query()->create([
            'laboratory_id' => $this->laboratory->id,
            'name' => 'Almacen Norte',
            'is_active' => true,
        ]);
    }

    public function test_it_resolves_a_main_list_with_a_backup_warehouse(): void
    {
        $configuration = $this->service()->resolve($this->request([
            'laboratory_id' => $this->laboratory->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'backup_enabled' => true,
            'backup_warehouse_id' => $this->backupWarehouse->id,
        ]));

        $this->assertSame($this->laboratory->id, $configuration['laboratory_id']);
        $this->assertSame($this->mainWarehouse->id, $configuration['warehouse_id']);
        $this->assertTrue($configuration['backup_enabled']);
        $this->assertSame($this->backupWarehouse->id, $configuration['backup_warehouse_id']);
        $this->assertFalse($configuration['is_backup']);
    }

    public function test_it_rejects_the_same_warehouse_as_main_and_backup(): void
    {
        try {
            $this->service()->resolve($this->request([
                'laboratory_id' => $this->laboratory->id,
                'warehouse_id' => $this->mainWarehouse->id,
                'backup_enabled' => true,
                'backup_warehouse_id' => $this->mainWarehouse->id,
            ]));

            $this->fail('La configuracion duplicada debio ser rechazada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('backup_warehouse_id', $exception->errors());
        }
    }

    public function test_it_rejects_a_warehouse_from_another_laboratory(): void
    {
        $otherLaboratory = Laboratory::query()->create([
            'nombre' => 'Monterrey',
            'activo' => true,
        ]);
        $foreignWarehouse = Warehouse::query()->create([
            'laboratory_id' => $otherLaboratory->id,
            'name' => 'Almacen Externo',
            'is_active' => true,
        ]);

        try {
            $this->service()->resolve($this->request([
                'laboratory_id' => $this->laboratory->id,
                'warehouse_id' => $this->mainWarehouse->id,
                'backup_enabled' => true,
                'backup_warehouse_id' => $foreignWarehouse->id,
            ]));

            $this->fail('El almacen de otra central debio ser rechazado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('backup_warehouse_id', $exception->errors());
        }
    }

    public function test_it_resolves_the_dedicated_backup_list_context(): void
    {
        $configuration = $this->service()->resolve($this->request([
            'laboratory_id' => $this->laboratory->id,
            'warehouse_id' => $this->backupWarehouse->id,
            'is_backup_list' => true,
            'primary_warehouse_id' => $this->mainWarehouse->id,
        ]));

        $this->assertTrue($configuration['is_backup']);
        $this->assertSame($this->backupWarehouse->id, $configuration['warehouse_id']);
        $this->assertSame($this->mainWarehouse->id, $configuration['primary_warehouse_id']);
        $this->assertFalse($configuration['backup_enabled']);
    }

    private function service(): PriceListWarehouseConfigurationService
    {
        return app(PriceListWarehouseConfigurationService::class);
    }

    private function request(array $data): Request
    {
        return Request::create('/price-lists', 'POST', $data);
    }
}
