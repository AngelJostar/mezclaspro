<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diluent_catalog_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diluent_id')->constrained()->cascadeOnDelete();
            $table->string('presentation');
            $table->decimal('volume_ml', 12, 2)->nullable();
            $table->string('commercial_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['diluent_id', 'presentation', 'commercial_name'], 'diluent_catalog_presentation_unique');
        });
        Schema::table('diluent_presentations', function (Blueprint $table) {
            $table->foreignId('catalog_presentation_id')->nullable()->after('diluent_id')->constrained('diluent_catalog_presentations')->nullOnDelete();
        });
        DB::table('diluent_presentations')->orderBy('id')->get()->each(function ($lot) {
            $catalogId = DB::table('diluent_catalog_presentations')->where('diluent_id', $lot->diluent_id)->where('presentation', $lot->presentacion)->where('commercial_name', $lot->denominacion_comercial)->value('id');
            if (! $catalogId) $catalogId = DB::table('diluent_catalog_presentations')->insertGetId(['diluent_id'=>$lot->diluent_id,'presentation'=>$lot->presentacion,'volume_ml'=>$lot->volume_ml,'commercial_name'=>$lot->denominacion_comercial,'manufacturer'=>$lot->fabricante,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('diluent_presentations')->where('id', $lot->id)->update(['catalog_presentation_id'=>$catalogId]);
        });
    }
    public function down(): void { Schema::table('diluent_presentations', fn (Blueprint $table) => $table->dropConstrainedForeignId('catalog_presentation_id')); Schema::dropIfExists('diluent_catalog_presentations'); }
};
