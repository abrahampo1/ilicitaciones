<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Desviación al alza entre lo adjudicado (adjudicacions.importe = PayableAmount) y el
 * presupuesto (licitacions.importe_total). Floor de importe para evitar % de ruido.
 */
class SobrecosteDetector implements Detector
{
    public function tipo(): string
    {
        return 'sobrecoste';
    }

    public function detect(): iterable
    {
        $pct = (float) config('periodico.umbrales.sobrecoste_pct');
        $base = (float) config('periodico.umbrales.sobrecoste_base_min');
        $cutoff = now()->subDays((int) config('periodico.ventana_dias'))->toDateString();

        $filas = DB::table('adjudicacions as a')
            ->join('licitacions as l', 'l.id', '=', 'a.licitacion_id')
            ->leftJoin('organismos as o', 'o.id', '=', 'l.organismo_id')
            ->leftJoin('empresas as e', 'e.id', '=', 'a.empresa_id')
            ->whereNotNull('a.importe')
            ->where('l.importe_total', '>=', $base)
            ->where('a.fecha_adjudicacion', '>=', $cutoff)
            // Umbral inline (no como binding): comparar una EXPRESIÓN contra un
            // parámetro ligado falla en SQLite (number < text). $pct es float de config.
            ->whereRaw('(a.importe - l.importe_total) / l.importe_total >= '.sprintf('%F', $pct))
            ->orderByRaw('(a.importe - l.importe_total) DESC')
            ->limit(200)
            ->get([
                'l.id as licitacion_id', 'l.identificador', 'l.titulo', 'l.importe_total',
                'a.importe',
                'o.id as organismo_id', 'o.nombre as organismo',
                'e.id as empresa_id', 'e.nombre as empresa',
            ]);

        foreach ($filas as $f) {
            $delta = (float) $f->importe - (float) $f->importe_total;
            $pctReal = $delta / (float) $f->importe_total;

            $score = Scoring::clamp($pctReal * 60, 0, 50)
                + Scoring::importe($delta, 100_000, 30, 50);

            $entidades = [['type' => 'licitacion', 'id' => (int) $f->licitacion_id, 'primary' => true]];
            if ($f->organismo_id) {
                $entidades[] = ['type' => 'organismo', 'id' => (int) $f->organismo_id];
            }
            if ($f->empresa_id) {
                $entidades[] = ['type' => 'empresa', 'id' => (int) $f->empresa_id];
            }

            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'alertas',
                signature: StoryCandidate::firma('sobrecoste', (string) $f->identificador),
                score: $score,
                payload: [
                    'tipo' => 'sobrecoste',
                    'licitacion' => ['identificador' => $f->identificador, 'titulo' => $f->titulo],
                    'presupuesto' => (float) $f->importe_total,
                    'importe_adjudicado' => (float) $f->importe,
                    'desviacion_absoluta' => round($delta, 2),
                    'desviacion_pct' => round($pctReal * 100, 1),
                    'organismo' => $f->organismo,
                    'empresa' => $f->empresa,
                ],
                entidades: $entidades,
            );
        }
    }
}
