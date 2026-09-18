<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inputs', function (Blueprint $table) {
            $table->unsignedTinyInteger('layout_column')->default(1)->after('orden_enum');
        });

        foreach ([[1, 2, 3, 8], [4], [5]] as $categoryIds) {
            $inputs = DB::table('inputs')
                ->whereIn('category_id', $categoryIds)
                ->orderBy('orden_enum')
                ->orderBy('id')
                ->get(['id']);

            foreach ($inputs as $index => $input) {
                DB::table('inputs')->where('id', $input->id)->update([
                    'layout_column' => ($index % 2) + 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inputs', function (Blueprint $table) {
            $table->dropColumn('layout_column');
        });
    }
};
