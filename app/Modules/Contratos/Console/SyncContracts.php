<?php

namespace Modules\Contratos\Console;

use Modules\Contratos\Jobs\DownloadPlacspMonth;
use Modules\Contratos\Jobs\ProcessPlacspFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class SyncContracts extends Command
{
    protected $signature = 'contracts:sync
        {--month= : Mes a sincronizar (formato YYYYMM, ej: 202603). Por defecto el mes actual}
        {--all : Descargar todos los meses disponibles desde 2018}
        {--sync : Ejecutar todo de forma síncrona (descarga + procesamiento, sin cola)}';

    protected $description = 'Descarga y procesa contratos de la PLACSP (Plataforma de Contratación del Sector Público)';

    private string $baseUrl = 'https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643';

    public function handle(): int
    {
        $months = $this->resolveMonths();

        if ($this->option('sync')) {
            return $this->handleSync($months);
        }

        return $this->handleAsync($months);
    }

    /**
     * Async: dispatch un job por mes → el worker descarga, extrae, y dispatcha atom jobs.
     */
    protected function handleAsync(\Illuminate\Support\Collection $months): int
    {
        $this->info("Dispatching {$months->count()} job(s) de descarga a la cola...");

        foreach ($months as $month) {
            DownloadPlacspMonth::dispatch($month);
            $this->line("  → Job dispatched: {$month}");
        }

        $this->info("Todos los jobs encolados. Ejecuta `php artisan queue:work` para procesarlos.");
        $this->info("Puedes monitorizar con: php artisan queue:monitor database:default");

        return self::SUCCESS;
    }

    /**
     * Sync: descarga y procesa todo en el proceso actual (para desarrollo/debug).
     */
    protected function handleSync(\Illuminate\Support\Collection $months): int
    {
        $this->info("Sincronización síncrona de {$months->count()} mes(es)...");

        $bar = $this->output->createProgressBar($months->count());

        foreach ($months as $month) {
            $this->processMonthSync($month);
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

    /**
     * Descarga y procesa un mes de forma síncrona (para --sync).
     */
    protected function processMonthSync(string $month): void
    {
        $zipFilename = "licitacionesPerfilesContratanteCompleto3_{$month}.zip";
        $url = "{$this->baseUrl}/{$zipFilename}";

        $dirPath = storage_path("app/placsp/{$month}");
        $zipPath = "{$dirPath}/{$zipFilename}";
        $extractPath = "{$dirPath}/extracted";

        // Si ya hay atom files extraídos, saltar descarga
        if (is_dir($extractPath) && count(glob("{$extractPath}/*.atom")) > 0) {
            $this->line(" {$month}: ya extraído, procesando atoms...");
            $this->processAtomFiles($extractPath);
            return;
        }

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $this->line(" Descargando {$zipFilename}...");

        try {
            $response = Http::timeout(600)
                ->connectTimeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ILicitaciones/1.0)'])
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            if (!$response->successful()) {
                $this->warn("  No disponible: {$url} (HTTP {$response->status()})");
                return;
            }

            $body = $response->body();

            if (strlen($body) < 4 || substr($body, 0, 2) !== "PK") {
                $this->warn("  Respuesta no es un ZIP válido para {$month}, saltando.");
                return;
            }

            file_put_contents($zipPath, $body);
            $sizeMb = round(strlen($body) / 1024 / 1024, 1);
            $this->line("  Descargado: {$sizeMb} MB");
        } catch (\Throwable $e) {
            $this->error("  Error descargando {$url}: {$e->getMessage()}");
            return;
        }

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
        @unlink($zipPath);

        $this->processAtomFiles($extractPath);
    }

    private function processAtomFiles(string $extractPath): void
    {
        $atomFiles = glob("{$extractPath}/*.atom");
        $this->line("  Encontrados " . count($atomFiles) . " ficheros ATOM");

        foreach ($atomFiles as $atomFile) {
            dispatch_sync(new ProcessPlacspFile($atomFile));
        }
    }
}
