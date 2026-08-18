<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['laboratory_id', 'name']);
        });

        $now = now();

        DB::table('laboratories')
            ->orderBy('id')
            ->get()
            ->each(function ($laboratory) use ($now) {
                DB::table('warehouses')->insert([
                    'laboratory_id' => $laboratory->id,
                    'name' => 'Almacen principal',
                    'state' => $laboratory->estado,
                    'address' => $laboratory->direccion,
                    'is_active' => (bool) $laboratory->activo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
