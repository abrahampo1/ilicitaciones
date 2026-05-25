<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Informe de un sector CPV: evolución interanual del gasto adjudicado.
 */
class InformeSectorialDetector implements Detector
{
    public function tipo(): string
    {
        return 'informe_sectorial';
    }

    public function detect(): iterable
    {
        $volMin = (float) config('periodico.umbrales.informe_volumen_min');

        $maxYear = DB::table('agregados_dimension')->where('dimension', 'cpv')->max('year');
        if ($maxYear === null) {
            return;
        }

        $cpvs = DB::table('agregados_dimension as ad')
            ->where('ad.dimension', 'cpv')->where('ad.year', $maxYear)
            ->where('ad.total_importe', '>=', $volMin)
            ->leftJoin('categorias as c', 'c.id', '=', 'ad.key_a')
            ->orderByDesc('ad.total_importe')
            ->limit(15)
            ->get(['ad.key_a as cpv_id', 'ad.total_importe', 'c.nombre as cpv']);

        foreach ($cpvs as $cpv) {
            $serie = DB::table('agregados_dimension')
                ->where('dimension', 'cpv')->where('key_a', $cpv->cpv_id)->whereNotNull('year')
                ->orderBy('year')
                ->get(['year', 'total_importe', 'num_adjudicaciones', 'num_empresas']);

            $serieArr = $serie->map(fn ($s) => [
                'year' => (int) $s->year,
                'importe' => (float) $s->total_importe,
                'num_adjudicaciones' => (int) $s->num_adjudicaciones,
                'num_empresas' => (int) $s->num_empresas,
            ])->all();

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'informes',
                signature: StoryCandidate::firma('informe_sectorial', 'cpv:'.$cpv->cpv_id, 'year:'.$maxYear),
                score: Scoring::importe((float) $cpv->total_importe, $volMin, 45, 60),
                payload: [
                    'tipo' => 'informe_sectorial',
                    'cpv' => $cpv->cpv,
                    'year_ultimo' => (int) $maxYear,
                    'volumen_ultimo' => (float) $cpv->total_importe,
                    'serie_anual' => $serieArr,
                ],
                entidades: [['type' => 'categoria', 'id' => (int) $cpv->cpv_id, 'primary' => true]],
            );
        }
    }
}
