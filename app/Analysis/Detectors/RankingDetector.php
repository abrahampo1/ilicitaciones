<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Ranking de empresas por sector CPV en el último año con datos.
 */
class RankingDetector implements Detector
{
    public function tipo(): string
    {
        return 'ranking';
    }

    public function detect(): iterable
    {
        $volMin = (float) config('periodico.umbrales.ranking_volumen_min');

        $maxYear = DB::table('agregados_dimension')->where('dimension', 'cpv')->max('year');
        if ($maxYear === null) {
            return;
        }

        $cpvs = DB::table('agregados_dimension as ad')
            ->where('ad.dimension', 'cpv')->where('ad.year', $maxYear)
            ->where('ad.total_importe', '>=', $volMin)
            ->leftJoin('categorias as c', 'c.id', '=', 'ad.key_a')
            ->orderByDesc('ad.total_importe')
            ->limit(20)
            ->get(['ad.key_a as cpv_id', 'ad.total_importe', 'ad.num_adjudicaciones', 'ad.num_empresas', 'c.nombre as cpv']);

        foreach ($cpvs as $cpv) {
            $top = DB::table('agregados_dimension as ad')
                ->where('ad.dimension', 'empresa_cpv')
                ->where('ad.key_b', $cpv->cpv_id)->where('ad.year', $maxYear)
                ->leftJoin('empresas as e', 'e.id', '=', 'ad.key_a')
                ->orderByDesc('ad.total_importe')
                ->limit(5)
                ->get(['ad.key_a as empresa_id', 'ad.total_importe', 'ad.num_adjudicaciones', 'e.nombre as empresa']);

            $ranking = $top->map(fn ($t) => [
                'empresa' => $t->empresa,
                'importe' => (float) $t->total_importe,
                'num_adjudicaciones' => (int) $t->num_adjudicaciones,
            ])->all();

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'rankings',
                signature: StoryCandidate::firma('ranking', 'cpv:'.$cpv->cpv_id, 'year:'.$maxYear),
                score: Scoring::importe((float) $cpv->total_importe, $volMin, 50, 70),
                payload: [
                    'tipo' => 'ranking',
                    'dimension' => 'cpv',
                    'cpv' => $cpv->cpv,
                    'year' => (int) $maxYear,
                    'volumen_total' => (float) $cpv->total_importe,
                    'num_adjudicaciones' => (int) $cpv->num_adjudicaciones,
                    'num_empresas' => (int) $cpv->num_empresas,
                    'ranking' => $ranking,
                ],
                entidades: array_merge(
                    [['type' => 'categoria', 'id' => (int) $cpv->cpv_id, 'primary' => true]],
                    $top->filter(fn ($t) => $t->empresa_id)->map(fn ($t) => ['type' => 'empresa', 'id' => (int) $t->empresa_id])->values()->all(),
                ),
            );
        }
    }
}
