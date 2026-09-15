<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixture_messages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 24);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('hospital_id');
            // Keep the record and author snapshot even if an account or mixture is removed.
            $table->unsignedBigInteger('author_id');
            $table->string('author_name');
            $table->string('sender_side', 16);
            $table->text('body');
            $table->uuid('client_token');
            $table->timestamp('created_at');
            $table->index(['kind', 'target_id', 'hospital_id', 'id'], 'mixture_message_history');
            $table->unique(['author_id', 'kind', 'target_id', 'client_token'], 'mixture_message_retry');
        });
        Schema::create('mixture_message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('kind', 24);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('last_message_id')->default(0);
            $table->timestamp('updated_at');
            $table->unique(['user_id', 'kind', 'target_id', 'hospital_id'], 'mixture_message_reader');
        });
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $trigger = 'mixture_messages_no_'.strtolower($operation);
            if (DB::getDriverName() === 'mysql') {
                DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON mixture_messages FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Messages are immutable'");
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON mixture_messages BEGIN SELECT RAISE(ABORT, 'Messages are immutable'); END");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mixture_message_reads');
        Schema::dropIfExists('mixture_messages');
    }
};
