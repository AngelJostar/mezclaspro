<?php

namespace App\Console\Commands;

use App\Services\MedicineRemainderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireMedicineRemainders extends Command
{
    protected $signature = 'inventory:expire-remainders';
    protected $description = 'Descarta remanentes de medicamentos cuya estabilidad ya vencio';

    public function handle(MedicineRemainderService $service): int
    {
        $count = DB::transaction(fn() => $service->expireDueRemainders());
        $this->info("Remanentes descartados: {$count}");

        return self::SUCCESS;
    }
}
