<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inputs')
            ->where('description', 'MULTIVITAMINICO ADULTO')
            ->update(['tipo_input' => 'adulto']);
    }

    public function down(): void
    {
        DB::table('inputs')
            ->where('description', 'MULTIVITAMINICO ADULTO')
            ->update(['tipo_input' => 'ambos']);
    }
};
