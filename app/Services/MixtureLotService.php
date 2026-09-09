<?php

namespace App\Services;

use App\Models\InspectionWaste;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Support\Facades\DB;

class MixtureLotService
{
    public function next(): string
    {
        $date = today();
        $months = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
        $prefix = 'L'.$date->format('d').$months[$date->month - 1].$date->format('y');

        return DB::transaction(function () use ($prefix) {
            // One locked counter serializes approvals and replacement lots for this day.
            DB::table('mixture_lot_sequences')->insertOrIgnore(['prefix' => $prefix, 'last_number' => 0]);
            $sequence = DB::table('mixture_lot_sequences')->where('prefix', $prefix)->lockForUpdate()->first();
            $last = (int) $sequence->last_number;
            if ($last === 0) {
                $lots = Mezcla::where('lote', 'like', $prefix.'%')->pluck('lote');
                $archived = InspectionWaste::where('snapshot->mixture->lote', 'like', $prefix.'%')->get()
                    ->map(fn ($waste) => $waste->snapshot['mixture']['lote']);
                foreach ($lots->concat($archived) as $lot) {
                    $suffix = substr($lot, strlen($prefix));
                    if (ctype_digit($suffix)) $last = max($last, (int) $suffix);
                }
            }
            $number = $last + 1;
            DB::table('mixture_lot_sequences')->where('prefix', $prefix)->update(['last_number' => $number]);

            return $prefix.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        });
    }
}
