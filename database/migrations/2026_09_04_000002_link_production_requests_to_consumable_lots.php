<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('production_supply_request_lines', function (Blueprint $table) { $table->unsignedBigInteger('diluent_presentation_id')->nullable()->change(); $table->foreignId('consumable_lot_id')->nullable()->after('diluent_presentation_id')->constrained('consumable_lots')->restrictOnDelete(); }); } public function down(): void { Schema::table('production_supply_request_lines', function (Blueprint $table) { $table->dropForeign(['consumable_lot_id']); $table->dropColumn('consumable_lot_id'); }); } };
