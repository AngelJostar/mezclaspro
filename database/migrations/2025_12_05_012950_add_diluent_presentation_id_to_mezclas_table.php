<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->unsignedBigInteger('diluent_presentation_id')
                ->nullable()
                ->after('volumen_dilucion');

            $table->foreign('diluent_presentation_id')
                ->references('id')
                ->on('diluent_presentations')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->dropForeign(['diluent_presentation_id']);
            $table->dropColumn('diluent_presentation_id');
        });
    }
};
