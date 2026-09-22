<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateInput('MULTIVITAMINICO PEDIÁTRICO', [
            'layout_column' => 1,
            'orden_enum' => 50,
        ]);
        $this->updateInput('MULTIVITAMINICO ADULTO', [
            'description' => 'MULTIVITAMINICO ADULTO',
            'layout_column' => 1,
            'orden_enum' => 55,
        ]);
        $this->updateInput('OLIGOELEMENTOS', [
            'layout_column' => 1,
            'orden_enum' => 60,
        ]);
        $this->updateInput('OLIGOMETALES', [
            'layout_column' => 1,
            'orden_enum' => 70,
        ]);
    }

    public function down(): void
    {
        $this->updateInput('MULTIVITAMINICO ADULTO', [
            'description' => 'Multivitaminico',
            'layout_column' => 2,
            'orden_enum' => 60,
        ]);
    }

    private function updateInput(string $catalogName, array $values): void
    {
        $inputId = DB::table('nutrition_medicines_catalog')
            ->where('denominacion_generica', $catalogName)
            ->value('input_id');

        if ($inputId) {
            DB::table('inputs')->where('id', $inputId)->update($values);
        }
    }
};
