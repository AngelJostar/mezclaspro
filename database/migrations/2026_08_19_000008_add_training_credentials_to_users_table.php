<?php

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('training_username')->nullable()->after('credential_password');
            $table->string('training_password')->nullable()->after('training_username');
            $table->text('training_credential_password')->nullable()->after('training_password');
        });

        User::query()->orderBy('id')->eachById(function (User $user) {
            try {
                $plainPassword = $user->credential_password;
            } catch (DecryptException) {
                $plainPassword = null;
            }

            $user->forceFill([
                'training_username' => User::suggestTrainingUsername($user->username, $user->id),
                'training_password' => $user->password,
                'training_credential_password' => $plainPassword,
            ])->saveQuietly();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('training_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['training_username']);
            $table->dropColumn([
                'training_username',
                'training_password',
                'training_credential_password',
            ]);
        });
    }
};
