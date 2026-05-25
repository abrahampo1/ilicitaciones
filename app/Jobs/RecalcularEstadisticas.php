<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula todos los agregados pesados fuera del ciclo request:
 *  - columnas total_importe / total_adjudicaciones de empresas
 *  - columnas total_importe / total_licitaciones de organismos
 *  - tabla inversiones_anuales (series por año para las fichas show)
 *  - tabla estadisticas (stats globales y tops del home)
 *
 * Se ejecuta tras cada importación y de forma programada. Es idempotente y
 * único en cola (no se solapan dos recálculos).
 */
class RecalcularEstadisticas implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function uniqueId(): string
    {
        return 'recalcular-estadisticas';
    }

    public function handle(): void
    {
        $this->recalcularEmpresas();
        $this->recalcularOrganismos();
        $this->recalcularInversionesAnuales();
        $this->recalcularEstadisticasGlobales();
        $this->limpiarCaches();
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function recalcularEmpresas(): void
    {
        if ($this->isMysql()) {
            // Camino rápido: agregación + JOIN en una sola pasada.
            DB::statement(<<<'SQL'
                UPDATE empresas e
                LEFT JOIN (
                    SELECT empresa_id, SUM(importe) AS s, COUNT(*) AS c
                    FROM adjudicacions
                    WHERE empresa_id IS NOT NULL
                    GROUP BY empresa_id
                ) a ON a.empresa_id = e.id
                SET e.total_importe = COALESCE(a.s, 0),
                    e.total_adjudicaciones = COALESCE(a.c, 0)
            SQL);

            return;
        }

        // Portable (sqlite / otros): subconsulta correlacionada.
        DB::statement(<<<'SQL'
            UPDATE empresas SET
                total_importe = COALESCE((SELECT SUM(importe) FROM adjudicacions WHERE adjudicacions.empresa_id = empresas.id), 0),
                total_adjudicaciones = COALESCE((SELECT COUNT(*) FROM adjudicacions WHERE adjudicacions.empresa_id = empresas.id), 0)
        SQL);
    }

    private function recalcularOrganismos(): void
    {
        if ($this->isMysql()) {
            DB::statement(<<<'SQL'
                UPDATE organismos o
                LEFT JOIN (
                    SELECT organismo_id, SUM(importe_total) AS s, COUNT(*) AS c
                    FROM licitacions
                    WHERE organismo_id IS NOT NULL
                    GROUP BY organismo_id
                ) l ON l.organismo_id = o.id
                SET o.total_importe = COALESCE(l.s, 0),
                    o.total_licitaciones = COALESCE(l.c, 0)
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            UPDATE organismos SET
                total_importe = COALESCE((SELECT SUM(importe_total) FROM licitacions WHERE licitacions.organismo_id = organismos.id), 0),
                total_licitaciones = COALESCE((SELECT COUNT(*) FROM licitacions WHERE licitacions.organismo_id = organismos.id), 0)
        SQL);
    }

    private function recalcularInversionesAnuales(): void
    {
        DB::table('inversiones_anuales')->truncate();

        $yearAdj = $this->yearExpr('fecha_adjudicacion');
        $yearLic = $this->yearExpr('fecha_actualizacion');

        DB::statement(<<<SQL
            INSERT INTO inversiones_anuales (entity_type, entity_id, year, total)
            SELECT 'empresa', empresa_id, {$yearAdj}, SUM(importe)
            FROM adjudicacions
            WHERE fecha_adjudicacion IS NOT NULL AND empresa_id IS NOT NULL
            GROUP BY empresa_id, {$yearAdj}
        SQL);

        DB::statement(<<<SQL
            INSERT INTO inversiones_anuales (entity_type, entity_id, year, total)
            SELECT 'organismo', organismo_id, {$yearLic}, SUM(importe_total)
            FROM licitacions
            WHERE fecha_actualizacion IS NOT NULL AND organismo_id IS NOT NULL
            GROUP BY organismo_id, {$yearLic}
        SQL);
    }

    private function yearExpr(string $column): string
    {
        return $this->isMysql()
            ? "YEAR({$column})"
            : "CAST(strftime('%Y', {$column}) AS INTEGER)";
    }

    private function recalcularEstadisticasGlobales(): void
    {
        $stats = [
            'latestDate' => DB::table('licitacions')->max('fecha_actualizacion'),
            'totalImporte' => (float) DB::table('licitacions')->sum('importe_total'),
            'conteoLicitaciones' => DB::table('licitacions')->count(),
            'totalOrganismos' => DB::table('organismos')->count(),
            'totalEmpresas' => DB::table('empresas')->count(),
            'totalVolumenAdjudicado' => (float) DB::table('adjudicacions')->sum('importe'),
        ];

        // Tops leídos ya de columnas precomputadas: ORDER BY sobre índice.
        $topEmpresas = DB::table('empresas')
            ->select('id as empresa_id', 'nombre', 'total_importe')
            ->where('total_adjudicaciones', '>', 0)
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get();

        $topOrganismos = DB::table('organismos')
            ->select('id as organismo_id', 'nombre', 'total_importe')
            ->where('total_licitaciones', '>', 0)
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get();

        $this->guardar('home_stats', $stats);
        $this->guardar('home_top_empresas', $topEmpresas);
        $this->guardar('home_top_organismos', $topOrganismos);
    }

    private function guardar(string $clave, $valor): void
    {
        DB::table('estadisticas')->updateOrInsert(
            ['clave' => $clave],
            ['valor' => json_encode($valor), 'updated_at' => now()]
        );
    }

    private function limpiarCaches(): void
    {
        // Los listados se cachean con clave por hash de filtros (empresas_*, organismos_*),
        // imposibles de enumerar. Tras recalcular hay que invalidarlos todos para que no
        // queden páginas con los agregados antiguos (p.ej. en cero antes del primer cálculo).
        Cache::flush();
    }
}
