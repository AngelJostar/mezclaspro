<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $mixtures = DB::table('mezclas')
            ->whereNotNull('remision')
            ->orderBy('id')
            ->get(['id', 'remision']);

        $requestRemissions = DB::table('solicitud_oncos')
            ->whereNotNull('remision')
            ->pluck('remision');

        $nextRemission = $mixtures
            ->pluck('remision')
            ->merge($requestRemissions)
            ->map(fn ($value) => is_numeric($value) ? (int) $value : 0)
            ->max() ?? 0;
        $seen = [];

        foreach ($mixtures as $mixture) {
            $originalRemission = (string) $mixture->remision;
            $remission = trim($originalRemission);

            if ($remission === '') {
                DB::table('mezclas')->where('id', $mixture->id)->update(['remision' => null]);

                continue;
            }

            $remissionKey = mb_strtolower($remission);

            if (isset($seen[$remissionKey])) {
                do {
                    $nextRemission++;
                    $remission = (string) $nextRemission;
                    $remissionKey = $remission;
                } while (isset($seen[$remissionKey]));

                DB::table('mezclas')
                    ->where('id', $mixture->id)
                    ->update(['remision' => $remission]);
            } elseif ($remission !== $originalRemission) {
                DB::table('mezclas')
                    ->where('id', $mixture->id)
                    ->update(['remision' => $remission]);
            }

            $seen[$remissionKey] = true;
        }

        Schema::table('mezclas', function (Blueprint $table) {
            $table->unique('remision', 'mezclas_remision_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->dropUnique('mezclas_remision_unique');
        });
    }
};
