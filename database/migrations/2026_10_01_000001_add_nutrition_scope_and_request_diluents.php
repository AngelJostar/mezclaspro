<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diluents', function (Blueprint $table) {
            $table->boolean('available_for_nutrition')->default(false)->after('denominacion_generica');
        });

        DB::table('diluents')
            ->where(function ($query) {
                $query->whereRaw('LOWER(denominacion_generica) LIKE ?', ['%agua%inyect%'])
                    ->orWhereRaw('LOWER(denominacion_generica) LIKE ?', ['%cloruro%de%sodio%']);
            })
            ->update(['available_for_nutrition' => true]);

        Schema::create('nutrition_solicitud_diluents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicituds')->cascadeOnDelete();
            $table->foreignId('diluent_id')->constrained('diluents')->restrictOnDelete();
            $table->foreignId('diluent_presentation_id')->constrained('diluent_presentations')->restrictOnDelete();
            $table->decimal('volume_ml', 12, 4);
            $table->string('generic_name');
            $table->string('commercial_name')->nullable();
            $table->string('presentation_name')->nullable();
            $table->string('lot')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['solicitud_id', 'diluent_id'], 'nutrition_request_diluent_unique');
            $table->index(['diluent_presentation_id', 'solicitud_id'], 'nutrition_diluent_presentation_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_solicitud_diluents');
        Schema::table('diluents', fn (Blueprint $table) => $table->dropColumn('available_for_nutrition'));
    }
};
