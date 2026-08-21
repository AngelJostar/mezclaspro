<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\User;
use App\Notifications\MixtureIntegrationStatusChanged;

class ExternalMixtureNotificationService
{
    public function notify(ExternalMixtureRequest $request): void
    {
        User::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user): bool => $user->can('nutricionales_solicitudes_index')
                && (! $user->hospital_id || $user->hospital_id === $request->hospital_id))
            ->each(fn (User $user) => $user->notify(new MixtureIntegrationStatusChanged($request)));
    }
}
