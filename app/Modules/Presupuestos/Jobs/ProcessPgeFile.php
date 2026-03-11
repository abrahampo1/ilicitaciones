<?php

namespace Modules\Presupuestos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;
use Modules\Presupuestos\Services\PgeParser;

class ProcessPgeFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public string $filePath,
        public int $year,
    ) {}

    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            Log::warning("PGE Process: fichero no encontrado: {$this->filePath}");
            return;
        }

        $parser = new PgeParser();
        $ext = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        // Detectar tipo de presupuesto por nombre de fichero
        $filename = strtolower(basename($this->filePath));
        $tipoPresupuesto = str_contains($filename, 'ingreso') ? 'ingresos' : 'gastos';

        $partidas = match ($ext) {
            'csv' => $parser->parseCsv($this->filePath, $tipoPresupuesto),
            'xml' => $parser->parseXml($this->filePath, $tipoPresupuesto),
            default => [],
        };

        if (empty($partidas)) {
            Log::info("PGE Process: sin partidas en {$this->filePath}");
            return;
        }

        // Obtener entidad Estado
        $entidad = EntidadPresupuestaria::where('tipo', EntidadPresupuestaria::TIPO_ESTADO)->first();
        if (!$entidad) {
            $entidad = EntidadPresupuestaria::create([
                'tipo' => EntidadPresupuestaria::TIPO_ESTADO,
                'nombre' => 'Estado',
            ]);
        }

        // Pre-cargar clasificaciones económicas
        $clasificaciones = ClasificacionPresupuestaria::tipo('economica')
            ->pluck('id', 'codigo')
            ->toArray();

        // Batch upsert
        $batch = [];
        foreach ($partidas as $p) {
            $batch[] = [
                'entidad_id' => $entidad->id,
                'ejercicio' => $this->year,
                'tipo_presupuesto' => $p['tipo_presupuesto'],
                'codigo_organica' => $p['codigo_organica'],
                'codigo_funcional' => $p['codigo_funcional'],
                'codigo_economica' => $p['codigo_economica'],
                'clasificacion_economica_id' => $clasificaciones[$p['codigo_economica']] ?? null,
                'credito_inicial' => $p['credito_inicial'],
                'fuente' => 'pge',
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

        Log::info("PGE Process: {$this->year} — " . count($partidas) . " partidas procesadas de {$this->filePath}");
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
