<?php

namespace Modules\Presupuestos\Console;

use Illuminate\Console\Command;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;

class SeedClasificaciones extends Command
{
    protected $signature = 'budgets:seed-clasificaciones';

    protected $description = 'Pre-carga los 9 capítulos económicos estándar de gastos e ingresos';

    public function handle(): int
    {
        $this->info('Seeding clasificaciones presupuestarias estándar...');

        $count = 0;

        // Capítulos de gastos
        foreach (ClasificacionPresupuestaria::CAPITULOS_GASTOS as $codigo => $nombre) {
            ClasificacionPresupuestaria::updateOrCreate(
                ['tipo' => 'economica', 'codigo' => "G{$codigo}"],
                [
                    'codigo_padre' => null,
                    'nivel' => 1,
                    'nombre' => $nombre,
                    'descripcion' => "Capítulo {$codigo} de gastos: {$nombre}",
                ]
            );
            $count++;
        }

        // Capítulos de ingresos
        foreach (ClasificacionPresupuestaria::CAPITULOS_INGRESOS as $codigo => $nombre) {
            ClasificacionPresupuestaria::updateOrCreate(
                ['tipo' => 'economica', 'codigo' => "I{$codigo}"],
                [
                    'codigo_padre' => null,
                    'nivel' => 1,
                    'nombre' => $nombre,
                    'descripcion' => "Capítulo {$codigo} de ingresos: {$nombre}",
                ]
            );
            $count++;
        }

        $this->info("Creadas/actualizadas {$count} clasificaciones estándar.");

        return self::SUCCESS;
    }
}
