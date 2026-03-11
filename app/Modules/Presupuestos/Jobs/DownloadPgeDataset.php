<?php

namespace Modules\Presupuestos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadPgeDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;
    public int $backoff = 60;

    public function __construct(
        public int $year,
    ) {
        $this->onQueue('downloads');
    }

    public function handle(): void
    {
        $dirPath = storage_path("app/presupuestos/pge/{$this->year}");

        // Si ya hay ficheros, dispatch process jobs directamente
        if (is_dir($dirPath)) {
            $files = glob("{$dirPath}/*.{csv,xml,CSV,XML}", GLOB_BRACE);
            if (count($files) > 0) {
                Log::info("PGE: {$this->year} ya descargado, dispatching process jobs...");
                foreach ($files as $file) {
                    ProcessPgeFile::dispatch($file, $this->year);
                }
                return;
            }
        }

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        Log::info("PGE: Buscando dataset para {$this->year} en datos.gob.es...");

        try {
            $response = Http::timeout(30)
                ->get('https://datos.gob.es/apidata/catalog/dataset', [
                    'q' => "presupuestos generales estado {$this->year}",
                    '_pageSize' => 5,
                    '_sort' => 'title',
                ]);

            if (!$response->successful()) {
                Log::warning("PGE: datos.gob.es no disponible (HTTP {$response->status()})");
                return;
            }

            $results = $response->json('result.items', $response->json('result', []));
            if (empty($results)) {
                Log::warning("PGE: No se encontraron datasets para {$this->year}");
                return;
            }

            foreach ($results as $dataset) {
                $distributions = $dataset['distribution'] ?? $dataset['resources'] ?? [];
                foreach ($distributions as $dist) {
                    $url = $dist['accessURL'] ?? $dist['downloadURL'] ?? $dist['url'] ?? null;
                    $format = strtolower($dist['format']['value'] ?? $dist['format'] ?? '');

                    if (!$url || !in_array($format, ['csv', 'xml', 'text/csv', 'application/xml'])) {
                        continue;
                    }

                    Log::info("PGE: Descargando {$url}");
                    $fileResponse = Http::timeout(120)->get($url);
                    if ($fileResponse->successful()) {
                        $ext = str_contains($format, 'xml') ? 'xml' : 'csv';
                        $filename = "pge_{$this->year}_" . md5($url) . ".{$ext}";
                        $filePath = "{$dirPath}/{$filename}";
                        file_put_contents($filePath, $fileResponse->body());
                        ProcessPgeFile::dispatch($filePath, $this->year);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("PGE: Error descargando {$this->year}: {$e->getMessage()}");
            throw $e;
        }
    }
}
