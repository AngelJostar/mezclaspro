<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WorkAgents extends Command
{
    protected $signature = 'agents:work';
    protected $description = 'Mantiene activo únicamente el programador de agentes (no ejecuta otras tareas de inventario).';

    public function handle(): int
    {
        $this->info('Programador de agentes iniciado.');
        while (true) {
            $lock = Cache::lock('agents-scheduler-tick', 600);
            if ($lock->get()) {
                try { $this->call('agents:run-due'); }
                finally { $lock->release(); }
            }
            sleep(60);
        }
    }
}
