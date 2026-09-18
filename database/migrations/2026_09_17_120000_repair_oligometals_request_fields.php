<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->synchronize('OLIGOMETALES', 'Oligometales');
        $this->synchronize('OLIGOELEMENTOS', 'Oligoelementos');
    }

    public function down(): void
    {
        $this->synchronize('OLIGOMETALES', 'Oligoelementos');

        $catalog = DB::table('nutrition_medicines_catalog')
            ->whereRaw('UPPER(denominacion_generica) = ?', ['OLIGOELEMENTOS'])
            ->first();

        if ($catalog?->input_id) {
            DB::table('inputs')->where('id', $catalog->input_id)->update([
                'description' => 'Oligoelementos Tracefusin',
                'is_active' => false,
            ]);
        }
    }

    private function synchronize(string $genericName, string $fieldName): void
    {
        $catalog = DB::table('nutrition_medicines_catalog')
            ->whereRaw('UPPER(denominacion_generica) = ?', [$genericName])
            ->first();

        if (!$catalog?->input_id) {
            return;
        }

        DB::table('inputs')->where('id', $catalog->input_id)->update([
            'description' => $fieldName,
            'category_id' => $catalog->category_id,
            'is_active' => true,
        ]);
    }
};
