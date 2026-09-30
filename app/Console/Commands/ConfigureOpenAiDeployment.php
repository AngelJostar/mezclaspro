<?php

namespace App\Console\Commands;

use App\Models\AiAgentProviderSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ConfigureOpenAiDeployment extends Command
{
    protected $signature = 'agents:configure-openai {--from-stdin : Leer una clave opcional desde stdin, nunca como argumento} {--model= : Modelo; si se omite conserva el configurado} {--verify : Comprobar acceso al modelo sin enviar datos clinicos}';

    protected $description = 'Configura OpenAI para despliegue, cifrando la clave con APP_KEY del servidor';

    public function handle(): int
    {
        if (!Schema::hasTable('ai_agent_provider_settings')) {
            $this->error('Ejecuta las migraciones antes de configurar OpenAI.');
            return self::FAILURE;
        }
        if (!config('app.key')) {
            $this->error('Falta APP_KEY del servidor. No copies ni regeneres la clave de otra instalacion.');
            return self::FAILURE;
        }

        $key = $this->option('from-stdin') ? $this->readKey() : '';
        if ($key !== '' && !preg_match('/^sk-[A-Za-z0-9_-]{16,500}$/D', $key)) {
            $this->error('Formato de clave API invalido. Revisa el secreto, sin publicarlo en registros.');
            return self::FAILURE;
        }

        try {
            $settings = AiAgentProviderSetting::firstOrNew(['id' => 1]);
            $storedKey = $settings->api_key;
            $effectiveKey = $key ?: ($storedKey ?: config('services.openai.api_key'));
            $model = trim((string) ($this->option('model') ?: ($settings->model ?: config('services.openai.model'))));
            if (!$effectiveKey) {
                $this->error('OpenAI sin configurar: agrega OPENAI_API_KEY a los secretos de GitHub o al entorno del VPS.');
                return self::FAILURE;
            }
            if (!preg_match('/^[a-zA-Z0-9._:-]{1,100}$/D', $model)) {
                $this->error('Identificador de modelo invalido.');
                return self::FAILURE;
            }

            // Verify before changing working credentials. This checks access, not available credits.
            if ($this->option('verify')) {
                $response = Http::withToken($effectiveKey)->acceptJson()->connectTimeout(5)->timeout(20)
                    ->withOptions(['allow_redirects' => false])
                    ->get('https://api.openai.com/v1/models/'.rawurlencode($model));
                if (!$response->successful() || $response->json('id') !== $model) {
                    $this->error('OpenAI no confirmo acceso al modelo (HTTP '.$response->status().'). Configuracion anterior conservada.');
                    return self::FAILURE;
                }
            }

            // Avoid rotating ciphertext/timestamps on every deploy: existing review receipts bind to them.
            if ($key !== '' && ($storedKey === null || !hash_equals($storedKey, $key))) $settings->api_key = $key;
            $settings->model = $model;
            if (!$settings->exists || $settings->isDirty()) $settings->save();
        } catch (\Throwable $e) {
            $this->error('No se pudo configurar OpenAI. Revisa conexion, migraciones y APP_KEY original del VPS. No se muestran datos del proveedor.');
            return self::FAILURE;
        }

        $this->info('OpenAI configurado. Credenciales conservadas en el servidor, nunca en Git.');
        if ($this->option('verify')) $this->info('Acceso al modelo verificado; esta comprobacion no valida cuota ni criterios clinicos.');
        return self::SUCCESS;
    }

    protected function readKey(): string
    {
        return trim((string) stream_get_contents(STDIN, 1024));
    }
}
