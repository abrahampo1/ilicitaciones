<?php

namespace Modules\Presupuestos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Presupuestos\Models\EjecucionPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

class ProcessEjecucionPge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public string $filePath,
        public int $year,
        public string $periodo, // 'anual', '2024-Q1', '2024-03', etc.
    ) {}

    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            Log::warning("Ejecución PGE: fichero no encontrado: {$this->filePath}");
            return;
        }

        $content = file_get_contents($this->filePath);
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');

        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines), ';');
        $headers = array_map('trim', $headers);

        // Pre-cargar partidas del PGE para este año
        $partidas = PartidaPresupuestaria::where('ejercicio', $this->year)
            ->where('fuente', 'pge')
            ->get()
            ->keyBy(fn($p) => "{$p->codigo_organica}|{$p->codigo_funcional}|{$p->codigo_economica}");

        $count = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $row = str_getcsv($line, ';');
            if (count($row) < count($headers)) continue;

            $data = array_combine($headers, $row);

            // Construir clave para buscar partida
            $key = trim($data['CodOrg'] ?? $data['Orgánica'] ?? '') . '|'
                 . trim($data['CodFun'] ?? $data['Funcional'] ?? '') . '|'
                 . trim($data['CodEco'] ?? $data['Económica'] ?? '');

            $partida = $partidas[$key] ?? null;
            if (!$partida) continue;

            $cleanNum = fn($val) => $val !== null && $val !== ''
                ? (float) str_replace(['.', ' '], '', str_replace(',', '.', $val))
                : null;

            EjecucionPresupuestaria::updateOrCreate(
                ['partida_id' => $partida->id, 'periodo' => $this->periodo],
                [
                    'credito_autorizado' => $cleanNum($data['Autorizado'] ?? $data['CreditoAutorizado'] ?? null),
                    'credito_dispuesto' => $cleanNum($data['Dispuesto'] ?? $data['CreditoDispuesto'] ?? null),
                    'obligaciones' => $cleanNum($data['Obligaciones'] ?? $data['ObligacionesReconocidas'] ?? null),
                    'pagos_propuestos' => $cleanNum($data['PagosPropuestos'] ?? null),
                    'pagos_realizados' => $cleanNum($data['PagosRealizados'] ?? $data['Pagos'] ?? null),
                    'remanentes' => $cleanNum($data['Remanentes'] ?? null),
                    'fuente' => 'pge',
                ]
            );

            $count++;
        }

        Log::info("Ejecución PGE: {$this->year}/{$this->periodo} — {$count} registros procesados");
    }
}
