<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserAccessService
{
    public function block(User $user): void
    {
        $user->forceFill([
            'is_active' => false,
            'remember_token' => Str::random(60),
        ])->save();

        if (Schema::hasTable('personal_access_tokens')) {
            $user->tokens()->delete();
        }

        if (config('session.driver') !== 'database') {
            return;
        }

        $sessionTable = (string) config('session.table', 'sessions');

        if (Schema::hasTable($sessionTable)) {
            DB::table($sessionTable)->where('user_id', $user->id)->delete();
        }
    }
}
