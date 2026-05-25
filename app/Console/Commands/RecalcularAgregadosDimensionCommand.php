<?php

namespace App\Console\Commands;

use App\Jobs\RecalcularAgregadosDimension;
use Illuminate\Console\Command;

class RecalcularAgregadosDimensionCommand extends Command
{
    protected $signature = 'app:recalcular-agregados-dimension {--sync : Ejecutar en el momento en vez de encolar}';

    protected $description = 'Recalcula agregados por dimensión (CPV, provincia, pares) para rankings y detectores';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Recalculando agregados de dimensión...');
            (new RecalcularAgregadosDimension)->handle();
            $this->info('✓ Agregados de dimensión recalculados.');
        } else {
            RecalcularAgregadosDimension::dispatch();
            $this->info('✓ Recálculo de agregados encolado.');
        }

        return self::SUCCESS;
    }
}
