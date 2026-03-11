<?php

namespace Modules\Contratos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DownloadPlacspMonth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;
    public int $backoff = 60;

    private static string $baseUrl = 'https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643';

    public function __construct(
        public string $month,
    ) {
        $this->onQueue('downloads');
    }

    public function handle(): void
    {
        $zipFilename = "licitacionesPerfilesContratanteCompleto3_{$this->month}.zip";
        $url = self::$baseUrl . "/{$zipFilename}";

        $dirPath = storage_path("app/placsp/{$this->month}");
        $zipPath = "{$dirPath}/{$zipFilename}";
        $extractPath = "{$dirPath}/extracted";

        // Si ya hay ficheros atom extraídos, saltar descarga
        if (is_dir($extractPath) && count(glob("{$extractPath}/*.atom")) > 0) {
            Log::info("PLACSP: {$this->month} ya extraído, dispatching atom jobs...");
            $this->dispatchAtomJobs($extractPath);
            return;
        }

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        Log::info("PLACSP: Descargando {$this->month}...");

        try {
            $response = Http::timeout(600)
                ->connectTimeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ILicitaciones/1.0)'])
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("PLACSP: No disponible {$this->month} (HTTP {$response->status()})");
                return;
            }

            $body = $response->body();

            if (strlen($body) < 4 || substr($body, 0, 2) !== "PK") {
                Log::warning("PLACSP: Respuesta no es ZIP válido para {$this->month}");
                return;
            }

            file_put_contents($zipPath, $body);
            $sizeMb = round(strlen($body) / 1024 / 1024, 1);
            Log::info("PLACSP: {$this->month} descargado ({$sizeMb} MB)");
        } catch (\Throwable $e) {
            Log::error("PLACSP: Error descargando {$this->month}: {$e->getMessage()}");
            throw $e; // Re-throw para que el job se reintente
        }

        // Extraer
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            Log::error("PLACSP: Error abriendo ZIP {$this->month}");
            return;
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Limpiar ZIP para liberar disco
        @unlink($zipPath);

        $this->dispatchAtomJobs($extractPath);
    }

    private function dispatchAtomJobs(string $extractPath): void
    {
        $atomFiles = glob("{$extractPath}/*.atom");
        Log::info("PLACSP: {$this->month} — " . count($atomFiles) . " ficheros ATOM, dispatching jobs...");

        foreach ($atomFiles as $atomFile) {
            ProcessPlacspFile::dispatch($atomFile);
        }
    }
}
