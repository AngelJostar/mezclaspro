<?php

namespace App\Services;

use App\Models\MedicineRemainder;
use App\Models\MedicineRemainderMovement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MedicineRemainderService
{
    private const EPSILON = 0.0001;

    public function expireDueRemainders(?CarbonInterface $at = null): int
    {
        $at ??= now();
        $expired = MedicineRemainder::query()
            ->where('is_active', true)
            ->where('current_ml', '>', 0)
            ->whereNotNull('usable_until')
            ->where('usable_until', '<=', $at)
            ->lockForUpdate()
            ->get();

        foreach ($expired as $remainder) {
            $this->discard($remainder, 'Estabilidad vencida', null, null, null, $at);
        }

        return $expired->count();
    }

    public function available(
        string $domain,
        int $presentationId,
        int $laboratoryId,
        ?int $warehouseId = null
    ): Collection {
        $this->expireDueRemainders();

        $presentationColumn = $domain === 'oncologico'
            ? 'medicine_presentation_id'
            : 'nutrition_medicine_presentation_id';

        return MedicineRemainder::query()
            ->where('domain', $domain)
            ->where($presentationColumn, $presentationId)
            ->where('laboratory_id', $laboratoryId)
            ->when($warehouseId, fn($query) => $query->where('warehouse_id', $warehouseId))
            ->where('is_active', true)
            ->where('current_ml', '>', self::EPSILON)
            ->where(function ($query) {
                $query->whereNull('usable_until')->orWhere('usable_until', '>', now());
            })
            ->orderByRaw('usable_until IS NULL ASC')
            ->orderBy('usable_until')
            ->orderBy('opened_at')
            ->lockForUpdate()
            ->get();
    }

    public function consumeAvailable(
        string $domain,
        int $presentationId,
        int $laboratoryId,
        float $requiredMl,
        string $referenceType,
        int $referenceId,
        ?int $warehouseId = null,
        ?int $userId = null
    ): array {
        $remaining = max(0, $requiredMl);
        $consumed = 0.0;
        $allocations = [];

        foreach ($this->available($domain, $presentationId, $laboratoryId, $warehouseId) as $remainder) {
            if ($remaining <= self::EPSILON) {
                break;
            }

            $before = (float) $remainder->current_ml;
            $quantity = min($before, $remaining);
            $after = max(0, $before - $quantity);

            $remainder->update([
                'current_ml' => $after,
                'is_active' => $after > self::EPSILON,
            ]);

            $this->movement(
                $remainder,
                'consumo',
                $quantity,
                $before,
                $after,
                $referenceType,
                $referenceId,
                $userId,
                'Consumo preferente de remanente vigente.'
            );

            $allocations[] = [
                'remainder_id' => $remainder->id,
                'medicine_laboratory_stock_id' => $remainder->medicine_laboratory_stock_id,
                'medicine_batch_id' => $remainder->medicine_batch_id,
                'quantity_ml' => round($quantity, 4),
                'lote' => $remainder->lote,
                'usable_until' => $remainder->usable_until,
            ];
            $consumed += $quantity;
            $remaining -= $quantity;
        }

        return [
            'consumed_ml' => round($consumed, 4),
            'remaining_ml' => round(max(0, $remaining), 4),
            'allocations' => $allocations,
        ];
    }

    public function openContainer(array $attributes, float $containerMl, float $usedMl): ?MedicineRemainder
    {
        $containerMl = max(0, $containerMl);
        $usedMl = min(max(0, $usedMl), $containerMl);
        $remainingMl = $containerMl - $usedMl;

        if ($remainingMl <= self::EPSILON) {
            return null;
        }

        $openedAt = $attributes['opened_at'] ?? now();
        $stabilityHours = (int) ($attributes['stability_hours'] ?? 0);

        // Sin estabilidad configurada el sobrante no es seguro para reutilizarse.
        if ($stabilityHours <= 0) {
            return null;
        }

        $usableUntil = $stabilityHours > 0
            ? $openedAt->copy()->addHours($stabilityHours)
            : null;

        $remainder = MedicineRemainder::create([
            'domain' => $attributes['domain'],
            'laboratory_id' => $attributes['laboratory_id'],
            'warehouse_id' => $attributes['warehouse_id'] ?? null,
            'nutrition_medicine_presentation_id' => $attributes['nutrition_medicine_presentation_id'] ?? null,
            'medicine_presentation_id' => $attributes['medicine_presentation_id'] ?? null,
            'medicine_laboratory_stock_id' => $attributes['medicine_laboratory_stock_id'] ?? null,
            'medicine_batch_id' => $attributes['medicine_batch_id'] ?? null,
            'lote' => $attributes['lote'] ?? null,
            'caducidad' => $attributes['caducidad'] ?? null,
            'opened_at' => $openedAt,
            'usable_until' => $usableUntil,
            'initial_ml' => $remainingMl,
            'current_ml' => $remainingMl,
            'is_active' => true,
            'opened_for_type' => $attributes['reference_type'] ?? null,
            'opened_for_id' => $attributes['reference_id'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);

        $this->movement(
            $remainder,
            'apertura',
            $remainingMl,
            0,
            $remainingMl,
            $attributes['reference_type'] ?? null,
            $attributes['reference_id'] ?? null,
            $attributes['user_id'] ?? null,
            'Remanente generado al abrir un envase nuevo.'
        );

        return $remainder;
    }

    public function discard(
        MedicineRemainder $remainder,
        string $reason,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?CarbonInterface $at = null
    ): void {
        $before = (float) $remainder->current_ml;

        $remainder->update([
            'current_ml' => 0,
            'is_active' => false,
            'discarded_at' => $at ?? now(),
            'discard_reason' => $reason,
        ]);

        $this->movement(
            $remainder,
            'descarte',
            $before,
            $before,
            0,
            $referenceType,
            $referenceId,
            $userId,
            $reason
        );
    }

    public function rollbackReference(string $referenceType, int $referenceId, ?int $userId = null): void
    {
        $consumptions = MedicineRemainderMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('movement_type', 'consumo')
            ->lockForUpdate()
            ->get();

        foreach ($consumptions as $movement) {
            $remainder = MedicineRemainder::query()->lockForUpdate()->find($movement->medicine_remainder_id);
            if (!$remainder) {
                continue;
            }

            $before = (float) $remainder->current_ml;
            $after = $before + (float) $movement->quantity_ml;
            $remainder->update([
                'current_ml' => $after,
                'is_active' => !$remainder->usable_until || $remainder->usable_until->isFuture(),
            ]);

            $this->movement(
                $remainder,
                'devolucion',
                (float) $movement->quantity_ml,
                $before,
                $after,
                $referenceType,
                $referenceId,
                $userId,
                'Devolucion de remanente por reversion de la operacion.'
            );

            // Conserva el movimiento para auditoria y evita devolverlo dos veces.
            $movement->update([
                'movement_type' => 'ajuste',
                'notes' => trim(($movement->notes ? $movement->notes . ' ' : '') . '[CONSUMO REVERTIDO]'),
            ]);
        }

        $opened = MedicineRemainder::query()
            ->where('opened_for_type', $referenceType)
            ->where('opened_for_id', $referenceId)
            ->lockForUpdate()
            ->get();

        foreach ($opened as $remainder) {
            $usedElsewhere = $remainder->movements()
                ->where('movement_type', 'consumo')
                ->where(function ($query) use ($referenceType, $referenceId) {
                    $query->where('reference_type', '!=', $referenceType)
                        ->orWhere('reference_id', '!=', $referenceId);
                })
                ->exists();

            if ($usedElsewhere) {
                throw new \RuntimeException(
                    'No se puede editar la mezcla porque uno de sus remanentes ya fue utilizado en otra preparacion.'
                );
            }

            $remainder->delete();
        }
    }

    private function movement(
        MedicineRemainder $remainder,
        string $type,
        float $quantity,
        float $before,
        float $after,
        ?string $referenceType,
        ?int $referenceId,
        ?int $userId,
        ?string $notes
    ): void {
        MedicineRemainderMovement::create([
            'medicine_remainder_id' => $remainder->id,
            'user_id' => $userId,
            'movement_type' => $type,
            'quantity_ml' => $quantity,
            'stock_before_ml' => $before,
            'stock_after_ml' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }
}
