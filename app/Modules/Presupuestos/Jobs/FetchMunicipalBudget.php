<?php

namespace Modules\Presupuestos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;
use Modules\Presupuestos\Services\GobiertoParser;

class FetchMunicipalBudget implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $codigoIne,
        public int $year,
        public string $area, // 'G' (gastos) o 'I' (ingresos)
        public string $kind, // 'economic' o 'functional'
    ) {}

    public function handle(): void
    {
        // Rate limit: 1 request per second to Gobierto
        Redis::throttle('gobierto')->allow(1)->every(1)->then(function () {
            $this->fetchAndProcess();
        }, function () {
            $this->release(5);
        });
    }

    protected function fetchAndProcess(): void
    {
        $url = "https://presupuestos.gobierto.es/api/data/{$this->codigoIne}/{$this->year}/{$this->area}/{$this->kind}.json";

        Log::info("Gobierto: Fetching {$url}");

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ILicitaciones/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("Gobierto: {$this->codigoIne}/{$this->year} no disponible (HTTP {$response->status()})");
                return;
            }

            $data = $response->json();
            if (empty($data)) {
                Log::info("Gobierto: Sin datos para {$this->codigoIne}/{$this->year}/{$this->area}/{$this->kind}");
                return;
            }
        } catch (\Throwable $e) {
            Log::error("Gobierto: Error fetching {$this->codigoIne}/{$this->year}: {$e->getMessage()}");
            throw $e;
        }

        // Obtener o crear entidad
        $entidad = EntidadPresupuestaria::where('codigo_ine', $this->codigoIne)->first();
        if (!$entidad) {
            $entidad = EntidadPresupuestaria::create([
                'tipo' => EntidadPresupuestaria::TIPO_MUNICIPIO,
                'nombre' => "Municipio {$this->codigoIne}",
                'codigo_ine' => $this->codigoIne,
            ]);
        }

        $parser = new GobiertoParser();
        $tipoPresupuesto = $this->area === 'G' ? 'gastos' : 'ingresos';

        // Upsert clasificaciones
        $clasificaciones = $parser->extractClasificaciones($data, $this->kind);
        foreach ($clasificaciones as $clas) {
            ClasificacionPresupuestaria::updateOrCreate(
                ['tipo' => $clas['tipo'], 'codigo' => $clas['codigo']],
                [
                    'codigo_padre' => $clas['codigo_padre'],
                    'nivel' => $clas['nivel'],
                    'nombre' => $clas['nombre'],
                ]
            );
        }

        // Parsear y upsert partidas
        $partidas = $parser->parseJson($data, $tipoPresupuesto, $this->kind);

        $batch = [];
        foreach ($partidas as $p) {
            $batch[] = [
                'entidad_id' => $entidad->id,
                'ejercicio' => $this->year,
                'tipo_presupuesto' => $p['tipo_presupuesto'],
                'codigo_organica' => $p['codigo_organica'] ?? null,
                'codigo_funcional' => $p['codigo_funcional'] ?? null,
                'codigo_economica' => $p['codigo_economica'] ?? null,
                'credito_inicial' => $p['credito_inicial'],
                'fuente' => 'gobierto',
                'synced_at' => now(),
            ];

            if (count($batch) >= 500) {
                $this->upsertBatch($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->upsertBatch($batch);
        }

        Log::info("Gobierto: {$this->codigoIne}/{$this->year}/{$this->area}/{$this->kind} — " . count($partidas) . " partidas");
    }

    protected function upsertBatch(array $batch): void
    {
        PartidaPresupuestaria::upsert(
            $batch,
            ['entidad_id', 'ejercicio', 'tipo_presupuesto', 'codigo_organica', 'codigo_funcional', 'codigo_economica', 'fuente'],
            ['credito_inicial', 'synced_at']
        );
    }
}
