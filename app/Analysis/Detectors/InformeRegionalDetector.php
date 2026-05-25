<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Informe regional: gasto en licitaciones por provincia y su evolución anual.
 */
class InformeRegionalDetector implements Detector
{
    public function tipo(): string
    {
        return 'informe_regional';
    }

    public function detect(): iterable
    {
        $volMin = (float) config('periodico.umbrales.informe_volumen_min');

        $maxYear = DB::table('agregados_dimension')->where('dimension', 'provincia')->max('year');
        if ($maxYear === null) {
            return;
        }

        $provincias = DB::table('agregados_dimension')
            ->where('dimension', 'provincia')->where('year', $maxYear)
            ->where('total_importe', '>=', $volMin)
            ->orderByDesc('total_importe')
            ->limit(15)
            ->get(['key_a as provincia', 'total_importe', 'num_licitaciones']);

        foreach ($provincias as $prov) {
            $serie = DB::table('agregados_dimension')
                ->where('dimension', 'provincia')->where('key_a', $prov->provincia)->whereNotNull('year')
                ->orderBy('year')
                ->get(['year', 'total_importe', 'num_licitaciones']);

            $serieArr = $serie->map(fn ($s) => [
                'year' => (int) $s->year,
                'importe' => (float) $s->total_importe,
                'num_licitaciones' => (int) $s->num_licitaciones,
            ])->all();

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'informes',
                signature: StoryCandidate::firma('informe_regional', 'provincia:'.$prov->provincia, 'year:'.$maxYear),
                score: Scoring::importe((float) $prov->total_importe, $volMin, 45, 60),
                payload: [
                    'tipo' => 'informe_regional',
                    'provincia' => $prov->provincia,
                    'year_ultimo' => (int) $maxYear,
                    'volumen_ultimo' => (float) $prov->total_importe,
                    'num_licitaciones' => (int) $prov->num_licitaciones,
                    'serie_anual' => $serieArr,
                ],
                entidades: [],
            );
        }
    }
}
