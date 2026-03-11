<?php

namespace Modules\Presupuestos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Presupuestos\Jobs\DownloadPgeDataset;
use Modules\Presupuestos\Jobs\ProcessPgeFile;
use Modules\Presupuestos\Models\EntidadPresupuestaria;

class SyncPge extends Command
{
    protected $signature = 'budgets:sync-pge
        {--year= : Año a sincronizar (ej: 2025). Por defecto el año actual}
        {--all : Descargar todos los años disponibles (2016-actual)}
        {--sync : Ejecutar de forma síncrona (sin cola)}';

    protected $description = 'Descarga y procesa los Presupuestos Generales del Estado desde datos.gob.es';

    private string $ckanApi = 'https://datos.gob.es/apidata/catalog/dataset';

    public function handle(): int
    {
        $this->ensureEstadoEntity();

        $years = $this->resolveYears();
        $this->info("Sincronizando PGE para " . $years->count() . " año(s)...");

        if ($this->option('sync')) {
            return $this->handleSync($years);
        }

        return $this->handleAsync($years);
    }

    protected function handleAsync(\Illuminate\Support\Collection $years): int
    {
        foreach ($years as $year) {
            DownloadPgeDataset::dispatch($year);
            $this->line("  → Job dispatched: PGE {$year}");
        }

        $this->info("Jobs encolados. Ejecuta `php artisan queue:work` para procesarlos.");
        return self::SUCCESS;
    }

    protected function handleSync(\Illuminate\Support\Collection $years): int
    {
        $bar = $this->output->createProgressBar($years->count());

        foreach ($years as $year) {
            $this->processYearSync($year);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización PGE completada.');

        return self::SUCCESS;
    }

    protected function processYearSync(int $year): void
    {
        $dirPath = storage_path("app/presupuestos/pge/{$year}");

        // Buscar ficheros ya descargados
        if (is_dir($dirPath)) {
            $files = glob("{$dirPath}/*.{csv,xml,CSV,XML}", GLOB_BRACE);
            if (count($files) > 0) {
                $this->line(" {$year}: ficheros encontrados, procesando...");
                foreach ($files as $file) {
                    dispatch_sync(new ProcessPgeFile($file, $year));
                }
                return;
            }
        }

        // Buscar dataset en datos.gob.es
        $this->line(" Buscando dataset PGE {$year} en datos.gob.es...");

        try {
            $response = Http::timeout(30)
                ->get($this->ckanApi, [
                    'q' => "presupuestos generales estado {$year}",
                    '_pageSize' => 5,
                    '_sort' => 'title',
                ]);

            if (!$response->successful()) {
                $this->warn("  No se pudo consultar datos.gob.es (HTTP {$response->status()})");
                return;
            }

            $results = $response->json('result.items', $response->json('result', []));
            if (empty($results)) {
                $this->warn("  No se encontraron datasets para PGE {$year}");
                return;
            }

            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }

            // Descargar recursos CSV/XML del primer dataset relevante
            $downloaded = false;
            foreach ($results as $dataset) {
                $distributions = $dataset['distribution'] ?? $dataset['resources'] ?? [];
                foreach ($distributions as $dist) {
                    $url = $dist['accessURL'] ?? $dist['downloadURL'] ?? $dist['url'] ?? null;
                    $format = strtolower($dist['format']['value'] ?? $dist['format'] ?? '');

                    if (!$url || !in_array($format, ['csv', 'xml', 'text/csv', 'application/xml'])) {
                        continue;
                    }

                    $this->line("  Descargando: {$url}");
                    try {
                        $fileResponse = Http::timeout(120)->get($url);
                        if ($fileResponse->successful()) {
                            $ext = str_contains($format, 'xml') ? 'xml' : 'csv';
                            $filename = "pge_{$year}_" . md5($url) . ".{$ext}";
                            file_put_contents("{$dirPath}/{$filename}", $fileResponse->body());
                            dispatch_sync(new ProcessPgeFile("{$dirPath}/{$filename}", $year));
                            $downloaded = true;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("  Error descargando: {$e->getMessage()}");
                    }
                }
                if ($downloaded) break;
            }

            if (!$downloaded) {
                $this->warn("  No se encontraron recursos CSV/XML descargables para PGE {$year}");
            }
        } catch (\Throwable $e) {
            $this->error("  Error buscando PGE {$year}: {$e->getMessage()}");
        }
    }

    protected function resolveYears(): \Illuminate\Support\Collection
    {
        if ($this->option('all')) {
            return collect(range(2016, (int) date('Y')));
        }

        $year = $this->option('year') ?? date('Y');
        return collect([(int) $year]);
    }

    protected function ensureEstadoEntity(): void
    {
        EntidadPresupuestaria::firstOrCreate(
            ['tipo' => EntidadPresupuestaria::TIPO_ESTADO, 'nombre' => 'Estado'],
            ['metadata' => ['descripcion' => 'Presupuestos Generales del Estado']]
        );
    }
}
