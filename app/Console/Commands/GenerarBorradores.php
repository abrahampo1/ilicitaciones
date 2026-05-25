<?php

namespace App\Console\Commands;

use App\Jobs\GenerarBorradorArticulo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Selecciona los candidatos más noticiables y encola su redacción con IA, respetando
 * un tope diario (control de coste de la API).
 */
class GenerarBorradores extends Command
{
    protected $signature = 'app:generar-borradores
                            {--limit= : Máximo de candidatos a procesar en esta pasada}
                            {--min-score= : Score mínimo}
                            {--provider= : Proveedor LLM (anthropic|openai); por defecto el de config}
                            {--dry-run : Solo listar, sin encolar}';

    protected $description = 'Genera borradores de artículos con Claude para los candidatos pendientes';

    public function handle(): int
    {
        $minScore = (float) ($this->option('min-score') ?? config('periodico.generacion.min_score'));
        $limit = (int) ($this->option('limit') ?? config('periodico.generacion.limit_por_run'));
        $capDiario = (int) config('periodico.generacion.cap_diario');

        $generadosHoy = DB::table('story_candidates')
            ->where('generated_at', '>=', now()->startOfDay())
            ->count();

        $restante = max(0, $capDiario - $generadosHoy);
        if ($restante === 0) {
            $this->warn("Tope diario alcanzado ({$capDiario}). Nada que generar.");

            return self::SUCCESS;
        }

        $cupo = min($limit, $restante);

        $candidatos = DB::table('story_candidates')
            ->where('estado', 'pendiente')
            ->where('score', '>=', $minScore)
            ->orderByDesc('score')
            ->limit($cupo)
            ->get(['id', 'tipo', 'score']);

        if ($candidatos->isEmpty()) {
            $this->info('No hay candidatos elegibles.');

            return self::SUCCESS;
        }

        foreach ($candidatos as $c) {
            if ($this->option('dry-run')) {
                $this->line("• #{$c->id} {$c->tipo} (score ".round($c->score, 1).')');

                continue;
            }

            // Marca 'generando' (evita doble proceso) y encola el job por candidato.
            DB::table('story_candidates')->where('id', $c->id)->update(['estado' => 'generando', 'updated_at' => now()]);
            GenerarBorradorArticulo::dispatch($c->id, $this->option('provider') ?: null);
        }

        $accion = $this->option('dry-run') ? 'listados' : 'encolados';
        $this->info("{$candidatos->count()} candidatos {$accion}.");

        return self::SUCCESS;
    }
}
