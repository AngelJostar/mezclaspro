<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE mezclas MODIFY estado ENUM('pendiente','aprobada','dispensada','preparada','revisada','cancelada','entregada') NULL");
    }

    public function down(): void
    {
        DB::table('mezclas')->where('estado', 'dispensada')->update(['estado' => 'aprobada']);
        DB::statement("ALTER TABLE mezclas MODIFY estado ENUM('pendiente','aprobada','preparada','revisada','cancelada','entregada') NULL");
    }
};
