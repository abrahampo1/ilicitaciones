<?php

namespace Modules\Contratos\Console;

use Modules\Contratos\Jobs\ProcessPlacspFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class SyncContracts extends Command
{
    protected $signature = 'contracts:sync
        {--month= : Mes a sincronizar (formato YYYYMM, ej: 202603). Por defecto el mes actual}
        {--all : Descargar todos los meses disponibles desde 2018}
        {--sync : Ejecutar el procesamiento de forma síncrona en lugar de por cola}';

    protected $description = 'Descarga y procesa contratos de la PLACSP (Plataforma de Contratación del Sector Público)';

    private string $baseUrl = 'https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643';

    public function handle(): int
    {
        $months = $this->resolveMonths();

        $this->info("Sincronizando {$months->count()} mes(es) de contratos PLACSP...");

        $bar = $this->output->createProgressBar($months->count());

        foreach ($months as $month) {
            $this->processMonth($month);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sincronización completada.');

        return self::SUCCESS;
    }

    protected function resolveMonths(): \Illuminate\Support\Collection
    {
        if ($this->option('all')) {
            $months = collect();
            $start = now()->setDate(2018, 1, 1)->startOfMonth();
            $end = now()->startOfMonth();
            while ($start <= $end) {
                $months->push($start->format('Ym'));
                $start->addMonth();
            }
            return $months;
        }

        $month = $this->option('month') ?? now()->format('Ym');
        return collect([$month]);
    }

    protected function processMonth(string $month): void
    {
        $zipFilename = "licitacionesPerfilesContratanteCompleto3_{$month}.zip";
        $url = "{$this->baseUrl}/{$zipFilename}";

        $dirPath = storage_path("app/placsp/{$month}");
        $zipPath = "{$dirPath}/{$zipFilename}";

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $this->line(" Descargando {$zipFilename}...");

        try {
            $response = Http::timeout(120)
                ->withHeaders(['User-Agent' => 'ILicitaciones/1.0'])
                ->get($url);

            if (!$response->successful()) {
                $this->warn("  No disponible: {$url} (HTTP {$response->status()})");
                return;
            }

            file_put_contents($zipPath, $response->body());
        } catch (\Throwable $e) {
            $this->error("  Error descargando {$url}: {$e->getMessage()}");
            return;
        }

        $extractPath = "{$dirPath}/extracted";
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error("  Error abriendo ZIP: {$zipPath}");
            return;
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $atomFiles = glob("{$extractPath}/*.atom");
        $this->line("  Encontrados " . count($atomFiles) . " ficheros ATOM");

        foreach ($atomFiles as $atomFile) {
            if ($this->option('sync')) {
                dispatch_sync(new ProcessPlacspFile($atomFile));
            } else {
                ProcessPlacspFile::dispatch($atomFile);
            }
        }

        @unlink($zipPath);
    }
}
