<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Empresa que acapara una cuota dominante del gasto de un organismo en un año.
 * Lee los pares precomputados en agregados_dimension (empresa_organismo).
 */
class ConcentracionDetector implements Detector
{
    public function tipo(): string
    {
        return 'concentracion';
    }

    public function detect(): iterable
    {
        $shareMin = (float) config('periodico.umbrales.concentracion_share');
        $volMin = (float) config('periodico.umbrales.concentracion_volumen_min');
        $minContratos = (int) config('periodico.umbrales.concentracion_min_contratos');

        // Totales por organismo y año (denominador del share).
        $totales = DB::table('agregados_dimension')
            ->where('dimension', 'empresa_organismo')
            ->whereNotNull('year')
            ->groupBy('key_b', 'year')
            ->select('key_b as organismo_id', 'year')
            ->selectRaw('SUM(total_importe) as org_total')
            ->selectRaw('COUNT(*) as num_empresas')
            // Inline: el binding sobre una expresión (SUM) falla en SQLite (number < text).
            ->havingRaw('SUM(total_importe) >= '.sprintf('%F', $volMin))
            ->get()
            ->keyBy(fn ($r) => $r->organismo_id.':'.$r->year);

        if ($totales->isEmpty()) {
            return;
        }

        $pares = DB::table('agregados_dimension as ad')
            ->where('ad.dimension', 'empresa_organismo')
            ->whereNotNull('ad.year')
            ->where('ad.num_adjudicaciones', '>=', $minContratos)
            ->leftJoin('empresas as e', 'e.id', '=', 'ad.key_a')
            ->leftJoin('organismos as o', 'o.id', '=', 'ad.key_b')
            ->get([
                'ad.key_a as empresa_id', 'ad.key_b as organismo_id', 'ad.year',
                'ad.total_importe', 'ad.num_adjudicaciones',
                'e.nombre as empresa', 'o.nombre as organismo', 'o.provincia',
            ]);

        foreach ($pares as $p) {
            $total = $totales->get($p->organismo_id.':'.$p->year);
            if (! $total) {
                continue;
            }

            $orgTotal = (float) $total->org_total;
            $share = $orgTotal > 0 ? (float) $p->total_importe / $orgTotal : 0;

            if ($share < $shareMin) {
                continue;
            }

            $score = Scoring::clamp(($share - $shareMin) / max(0.01, 1 - $shareMin) * 50, 0, 50)
                + Scoring::importe((float) $p->total_importe, 1_000_000, 25, 30)
                + ((int) $p->num_adjudicaciones >= 10 ? 20 : 0);

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'alertas',
                signature: StoryCandidate::firma('concentracion', 'empresa:'.$p->empresa_id, 'organismo:'.$p->organismo_id, 'year:'.$p->year),
                score: Scoring::clamp($score, 0, 100),
                payload: [
                    'tipo' => 'concentracion',
                    'empresa' => $p->empresa,
                    'organismo' => $p->organismo,
                    'provincia' => $p->provincia,
                    'year' => (int) $p->year,
                    'importe_empresa' => (float) $p->total_importe,
                    'importe_organismo' => round($orgTotal, 2),
                    'share_pct' => round($share * 100, 1),
                    'num_adjudicaciones' => (int) $p->num_adjudicaciones,
                    'num_empresas_organismo' => (int) $total->num_empresas,
                ],
                entidades: [
                    ['type' => 'empresa', 'id' => (int) $p->empresa_id, 'primary' => true],
                    ['type' => 'organismo', 'id' => (int) $p->organismo_id],
                ],
            );
        }
    }
}
