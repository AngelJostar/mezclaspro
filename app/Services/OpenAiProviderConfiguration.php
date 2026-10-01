<?php

namespace App\Services;

use App\Models\AiAgentProviderSetting;

class OpenAiProviderConfiguration
{
    public static function resolve(?AiAgentProviderSetting $settings = null): array
    {
        $settings ??= AiAgentProviderSetting::find(1);
        $environmentKey = trim((string) config('services.openai.api_key'));
        $environmentModel = trim((string) config('services.openai.model'));
        $storedKey = $settings?->api_key;
        $storedModel = $settings?->model;
        $preferEnvironment = (bool) config('services.openai.prefer_environment', false);

        return [
            'key' => $preferEnvironment
                ? ($environmentKey ?: $storedKey)
                : ($storedKey ?: $environmentKey),
            'model' => $preferEnvironment
                ? ($environmentModel ?: $storedModel)
                : ($storedModel ?: $environmentModel),
            'source' => $preferEnvironment && $environmentKey !== '' ? 'environment' : ($storedKey ? 'database' : 'environment'),
            'settings' => $settings,
        ];
    }
}
