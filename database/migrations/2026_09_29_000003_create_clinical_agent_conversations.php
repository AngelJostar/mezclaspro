<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinical_agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('user_id');
            $table->longText('messages');
            $table->timestamps();
            $table->index(['user_id', 'agent_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_agent_conversations');
    }
};
