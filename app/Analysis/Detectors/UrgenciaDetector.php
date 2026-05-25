<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Organismo con proporción anómala de adjudicaciones marcadas como urgentes/emergencia.
 */
class UrgenciaDetector implements Detector
{
    public function tipo(): string
    {
        return 'urgencia';
    }

    public function detect(): iterable
    {
        $codigos = array_map('strval', (array) config('periodico.umbrales.urgencia_codigos'));
        $minTotal = (int) config('periodico.umbrales.urgencia_min_total');
        $ratioMin = (float) config('periodico.umbrales.urgencia_ratio');
        $cutoff = now()->subMonths((int) config('periodico.ventana_meses'))->toDateString();

        if (empty($codigos)) {
            return;
        }

        $ph = implode(',', array_fill(0, count($codigos), '?'));
        $caseCount = "SUM(CASE WHEN a.urgencia IN ($ph) THEN 1 ELSE 0 END)";
        $caseImporte = "SUM(CASE WHEN a.urgencia IN ($ph) THEN a.importe ELSE 0 END)";

        $filas = DB::table('adjudicacions as a')
            ->join('licitacions as l', 'l.id', '=', 'a.licitacion_id')
            ->join('organismos as o', 'o.id', '=', 'l.organismo_id')
            ->where('a.fecha_adjudicacion', '>=', $cutoff)
            ->groupBy('o.id', 'o.nombre', 'o.provincia')
            // Umbrales inline: comparar expresiones (COUNT/ratio) contra bindings falla
            // en SQLite. Solo los códigos del IN van como binding (columna TEXT).
            ->havingRaw('COUNT(*) >= '.(int) $minTotal)
            ->havingRaw("$caseCount * 1.0 / COUNT(*) >= ".sprintf('%F', $ratioMin), $codigos)
            ->select('o.id as organismo_id', 'o.nombre as organismo', 'o.provincia')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("$caseCount as urgentes", $codigos)
            ->selectRaw("$caseImporte as importe_urgente", $codigos)
            ->orderByDesc('total')
            ->limit(100)
            ->get();

        foreach ($filas as $f) {
            $ratio = (int) $f->total > 0 ? (float) $f->urgentes / (int) $f->total : 0;

            $score = Scoring::clamp($ratio * 50, 0, 50)
                + Scoring::importe((float) $f->importe_urgente, 1_000_000, 30, 40)
                + ((int) $f->total >= 50 ? 10 : 0);

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'alertas',
                signature: StoryCandidate::firma('urgencia', 'organismo:'.$f->organismo_id, 'year:'.now()->year),
                score: Scoring::clamp($score, 0, 100),
                payload: [
                    'tipo' => 'urgencia',
                    'organismo' => $f->organismo,
                    'provincia' => $f->provincia,
                    'total_adjudicaciones' => (int) $f->total,
                    'adjudicaciones_urgentes' => (int) $f->urgentes,
                    'ratio_urgencia_pct' => round($ratio * 100, 1),
                    'importe_urgente' => (float) $f->importe_urgente,
                    'ventana_meses' => (int) config('periodico.ventana_meses'),
                ],
                entidades: [['type' => 'organismo', 'id' => (int) $f->organismo_id, 'primary' => true]],
            );
        }
    }
}
