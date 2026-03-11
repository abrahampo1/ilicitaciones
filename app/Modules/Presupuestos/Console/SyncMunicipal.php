<?php

namespace Modules\Presupuestos\Console;

use Illuminate\Console\Command;
use Modules\Presupuestos\Jobs\FetchMunicipalBudget;
use Modules\Presupuestos\Models\EntidadPresupuestaria;

class SyncMunicipal extends Command
{
    protected $signature = 'budgets:sync-municipal
        {--ine= : Código INE del municipio (ej: 28079 para Madrid)}
        {--year= : Año a sincronizar (ej: 2015). Por defecto el año actual}
        {--all : Sincronizar todos los municipios registrados}
        {--sync : Ejecutar de forma síncrona (sin cola)}';

    protected $description = 'Descarga presupuestos municipales desde la API de Gobierto';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?? date('Y'));

        if ($this->option('ine')) {
            return $this->syncMunicipio($this->option('ine'), $year);
        }

        if ($this->option('all')) {
            return $this->syncAll($year);
        }

        $this->error('Especifica --ine=CÓDIGO o --all');
        return self::FAILURE;
    }

    protected function syncMunicipio(string $codigoIne, int $year): int
    {
        // Asegurar que la entidad existe
        $entidad = EntidadPresupuestaria::firstOrCreate(
            ['codigo_ine' => $codigoIne],
            [
                'tipo' => EntidadPresupuestaria::TIPO_MUNICIPIO,
                'nombre' => "Municipio {$codigoIne}",
            ]
        );

        $this->info("Sincronizando presupuesto de {$entidad->nombre} (INE: {$codigoIne}) para {$year}...");

        if ($this->option('sync')) {
            dispatch_sync(new FetchMunicipalBudget($codigoIne, $year, 'G', 'economic'));
            dispatch_sync(new FetchMunicipalBudget($codigoIne, $year, 'G', 'functional'));
            dispatch_sync(new FetchMunicipalBudget($codigoIne, $year, 'I', 'economic'));
            dispatch_sync(new FetchMunicipalBudget($codigoIne, $year, 'I', 'functional'));
        } else {
            FetchMunicipalBudget::dispatch($codigoIne, $year, 'G', 'economic');
            FetchMunicipalBudget::dispatch($codigoIne, $year, 'G', 'functional');
            FetchMunicipalBudget::dispatch($codigoIne, $year, 'I', 'economic');
            FetchMunicipalBudget::dispatch($codigoIne, $year, 'I', 'functional');
        }

        $this->info($this->option('sync') ? 'Sincronización completada.' : 'Jobs encolados.');
        return self::SUCCESS;
    }

    protected function syncAll(int $year): int
    {
        $municipios = EntidadPresupuestaria::tipo(EntidadPresupuestaria::TIPO_MUNICIPIO)
            ->whereNotNull('codigo_ine')
            ->get();

        if ($municipios->isEmpty()) {
            $this->warn('No hay municipios registrados. Importa primero un CSV con datos municipales.');
            return self::FAILURE;
        }

        $this->info("Sincronizando {$municipios->count()} municipios para {$year}...");

        $bar = $this->output->createProgressBar($municipios->count());

        foreach ($municipios as $mun) {
            if ($this->option('sync')) {
                dispatch_sync(new FetchMunicipalBudget($mun->codigo_ine, $year, 'G', 'economic'));
                dispatch_sync(new FetchMunicipalBudget($mun->codigo_ine, $year, 'I', 'economic'));
            } else {
                FetchMunicipalBudget::dispatch($mun->codigo_ine, $year, 'G', 'economic');
                FetchMunicipalBudget::dispatch($mun->codigo_ine, $year, 'I', 'economic');
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización municipal completada.');

        return self::SUCCESS;
    }
}
