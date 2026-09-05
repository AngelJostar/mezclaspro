<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consumable_catalog_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_item_id')->constrained()->cascadeOnDelete();
            $table->string('presentation');
            $table->string('commercial_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('consumable_lots', function (Blueprint $table) {
            $table->foreignId('catalog_presentation_id')->nullable()->after('consumable_item_id')->constrained('consumable_catalog_presentations')->nullOnDelete();
        });

        DB::table('consumable_lots')->orderBy('id')->get()->each(function ($lot) {
            $presentation = DB::table('consumable_catalog_presentations')->where([
                'consumable_item_id' => $lot->consumable_item_id,
                'presentation' => $lot->presentation ?: 'Sin presentación',
                'commercial_name' => $lot->brand,
                'manufacturer' => $lot->manufacturer,
            ])->first();
            $id = $presentation?->id ?: DB::table('consumable_catalog_presentations')->insertGetId([
                'consumable_item_id' => $lot->consumable_item_id,
                'presentation' => $lot->presentation ?: 'Sin presentación',
                'commercial_name' => $lot->brand,
                'manufacturer' => $lot->manufacturer,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('consumable_lots')->where('id', $lot->id)->update(['catalog_presentation_id' => $id]);
        });
    }

    public function down(): void
    {
        Schema::table('consumable_lots', fn (Blueprint $table) => $table->dropConstrainedForeignId('catalog_presentation_id'));
        Schema::dropIfExists('consumable_catalog_presentations');
    }
};
