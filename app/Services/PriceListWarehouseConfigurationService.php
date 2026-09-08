<?php

namespace App\Services;

use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PriceListWarehouseConfigurationService
{
    public function resolve(Request $request): array
    {
        $validated = Validator::make($request->all(), [
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'is_backup_list' => ['nullable', 'boolean'],
            'primary_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ], [
            'laboratory_id.required' => 'Selecciona la central de mezclas.',
            'warehouse_id.required' => 'Selecciona el almacén surtidor.',
            'backup_warehouse_id.exists' => 'El almacén de respaldo seleccionado no es válido.',
            'primary_warehouse_id.exists' => 'El almacén principal seleccionado no es válido.',
        ])->validate();

        $laboratory = Laboratory::query()
            ->whereKey($validated['laboratory_id'])
            ->where('activo', true)
            ->first();

        if (! $laboratory) {
            throw ValidationException::withMessages([
                'laboratory_id' => 'La central seleccionada no está activa.',
            ]);
        }

        $warehouse = $this->warehouseInLaboratory(
            (int) $validated['warehouse_id'],
            (int) $laboratory->id,
            'warehouse_id'
        );
        $isBackupList = $request->boolean('is_backup_list');

        if ($isBackupList) {
            $primaryWarehouseId = (int) ($validated['primary_warehouse_id'] ?? 0);

            if ($primaryWarehouseId <= 0) {
                throw ValidationException::withMessages([
                    'primary_warehouse_id' => 'Selecciona el almacén principal relacionado.',
                ]);
            }

            $primaryWarehouse = $this->warehouseInLaboratory(
                $primaryWarehouseId,
                (int) $laboratory->id,
                'primary_warehouse_id'
            );

            if ($primaryWarehouse->is($warehouse)) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'El almacén de respaldo debe ser distinto al almacén principal.',
                ]);
            }

            return [
                'laboratory_id' => $laboratory->id,
                'warehouse_id' => $warehouse->id,
                'backup_enabled' => false,
                'backup_warehouse_id' => null,
                'is_backup' => true,
                'primary_warehouse_id' => $primaryWarehouse->id,
            ];
        }

        $backupEnabled = $request->boolean('backup_enabled');
        $backupWarehouse = null;

        if ($backupEnabled) {
            $backupWarehouseId = (int) ($validated['backup_warehouse_id'] ?? 0);

            if ($backupWarehouseId <= 0) {
                throw ValidationException::withMessages([
                    'backup_warehouse_id' => 'Selecciona el almacén de respaldo.',
                ]);
            }

            $backupWarehouse = $this->warehouseInLaboratory(
                $backupWarehouseId,
                (int) $laboratory->id,
                'backup_warehouse_id'
            );

            if ($backupWarehouse->is($warehouse)) {
                throw ValidationException::withMessages([
                    'backup_warehouse_id' => 'El almacén de respaldo debe ser distinto al almacén principal.',
                ]);
            }
        }

        return [
            'laboratory_id' => $laboratory->id,
            'warehouse_id' => $warehouse->id,
            'backup_enabled' => $backupEnabled,
            'backup_warehouse_id' => $backupWarehouse?->id,
            'is_backup' => false,
            'primary_warehouse_id' => null,
        ];
    }

    private function warehouseInLaboratory(int $warehouseId, int $laboratoryId, string $field): Warehouse
    {
        $warehouse = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('laboratory_id', $laboratoryId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                $field => 'El almacén seleccionado no pertenece a la central indicada o no está activo.',
            ]);
        }

        return $warehouse;
    }
}
