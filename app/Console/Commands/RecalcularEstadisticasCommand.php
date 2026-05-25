<?php

namespace App\Console\Commands;

use App\Jobs\RecalcularEstadisticas;
use Illuminate\Console\Command;

class RecalcularEstadisticasCommand extends Command
{
    protected $signature = 'app:recalcular-estadisticas {--sync : Ejecutar en el momento en vez de encolar}';

    protected $description = 'Recalcula agregados (columnas, inversiones anuales, stats home)';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Recalculando en el momento...');
            (new RecalcularEstadisticas)->handle();
            $this->info('✓ Estadísticas recalculadas.');
        } else {
            RecalcularEstadisticas::dispatch();
            $this->info('✓ Recálculo encolado.');
        }

        return self::SUCCESS;
    }
}
