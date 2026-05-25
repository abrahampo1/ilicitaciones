<?php

namespace App\Jobs;

use App\Analysis\Concerns\DualDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Rellena agregados_dimension (por año) para las dimensiones que alimentan rankings,
 * informes y detectores de concentración. Idempotente: truncate + INSERT...SELECT,
 * mismo patrón dual-DB que RecalcularEstadisticas.
 */
class RecalcularAgregadosDimension implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, DualDatabase, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function uniqueId(): string
    {
        return 'recalcular-agregados-dimension';
    }

    public function handle(): void
    {
        DB::table('agregados_dimension')->truncate();

        $this->agregarCpv();
        $this->agregarProvincia();
        $this->agregarEmpresaOrganismo();
        $this->agregarEmpresaCpv();
    }

    private function agregarCpv(): void
    {
        $year = $this->yearExpr('a.fecha_adjudicacion');

        DB::statement(<<<SQL
            INSERT INTO agregados_dimension
                (dimension, key_a, key_b, year, total_importe, num_adjudicaciones, num_licitaciones, num_empresas)
            SELECT 'cpv', l.categoria_id, NULL, {$year},
                   COALESCE(SUM(a.importe), 0), COUNT(*), 0, COUNT(DISTINCT a.empresa_id)
            FROM adjudicacions a
            JOIN licitacions l ON l.id = a.licitacion_id
            WHERE l.categoria_id IS NOT NULL AND a.fecha_adjudicacion IS NOT NULL
            GROUP BY l.categoria_id, {$year}
        SQL);
    }

    private function agregarProvincia(): void
    {
        $year = $this->yearExpr('l.fecha_actualizacion');

        DB::statement(<<<SQL
            INSERT INTO agregados_dimension
                (dimension, key_a, key_b, year, total_importe, num_adjudicaciones, num_licitaciones, num_empresas)
            SELECT 'provincia', o.provincia, NULL, {$year},
                   COALESCE(SUM(l.importe_total), 0), 0, COUNT(*), 0
            FROM licitacions l
            JOIN organismos o ON o.id = l.organismo_id
            WHERE o.provincia IS NOT NULL AND o.provincia <> '' AND l.fecha_actualizacion IS NOT NULL
            GROUP BY o.provincia, {$year}
        SQL);
    }

    private function agregarEmpresaOrganismo(): void
    {
        $year = $this->yearExpr('a.fecha_adjudicacion');

        DB::statement(<<<SQL
            INSERT INTO agregados_dimension
                (dimension, key_a, key_b, year, total_importe, num_adjudicaciones, num_licitaciones, num_empresas)
            SELECT 'empresa_organismo', a.empresa_id, l.organismo_id, {$year},
                   COALESCE(SUM(a.importe), 0), COUNT(*), 0, 1
            FROM adjudicacions a
            JOIN licitacions l ON l.id = a.licitacion_id
            WHERE a.empresa_id IS NOT NULL AND l.organismo_id IS NOT NULL AND a.fecha_adjudicacion IS NOT NULL
            GROUP BY a.empresa_id, l.organismo_id, {$year}
        SQL);
    }

    private function agregarEmpresaCpv(): void
    {
        $year = $this->yearExpr('a.fecha_adjudicacion');

        DB::statement(<<<SQL
            INSERT INTO agregados_dimension
                (dimension, key_a, key_b, year, total_importe, num_adjudicaciones, num_licitaciones, num_empresas)
            SELECT 'empresa_cpv', a.empresa_id, l.categoria_id, {$year},
                   COALESCE(SUM(a.importe), 0), COUNT(*), 0, 1
            FROM adjudicacions a
            JOIN licitacions l ON l.id = a.licitacion_id
            WHERE a.empresa_id IS NOT NULL AND l.categoria_id IS NOT NULL AND a.fecha_adjudicacion IS NOT NULL
            GROUP BY a.empresa_id, l.categoria_id, {$year}
        SQL);
    }
}
