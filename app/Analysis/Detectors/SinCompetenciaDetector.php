<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Contratos relevantes adjudicados por procedimientos sin concurrencia.
 */
class SinCompetenciaDetector implements Detector
{
    public function tipo(): string
    {
        return 'sin_competencia';
    }

    public function detect(): iterable
    {
        $codigos = (array) config('periodico.umbrales.sin_competencia_codigos');
        $umbral = (float) config('periodico.umbrales.sin_competencia_importe');
        $cutoff = now()->subDays((int) config('periodico.ventana_dias'))->toDateString();

        if (empty($codigos)) {
            return;
        }

        $filas = DB::table('adjudicacions as a')
            ->join('licitacions as l', 'l.id', '=', 'a.licitacion_id')
            ->leftJoin('organismos as o', 'o.id', '=', 'l.organismo_id')
            ->leftJoin('empresas as e', 'e.id', '=', 'a.empresa_id')
            ->whereIn('a.tipo_procedimiento', $codigos)
            ->where('l.importe_total', '>=', $umbral)
            ->where('a.fecha_adjudicacion', '>=', $cutoff)
            ->orderByDesc('l.importe_total')
            ->limit(200)
            ->get([
                'l.id as licitacion_id', 'l.identificador', 'l.titulo', 'l.importe_total',
                'a.importe', 'a.tipo_procedimiento',
                'o.id as organismo_id', 'o.nombre as organismo',
                'e.id as empresa_id', 'e.nombre as empresa',
            ]);

        foreach ($filas as $f) {
            $score = Scoring::importe((float) $f->importe_total, $umbral, 45, 70) + 20;

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
                signature: StoryCandidate::firma('sin_competencia', (string) $f->identificador),
                score: Scoring::clamp($score, 0, 100),
                payload: [
                    'tipo' => 'sin_competencia',
                    'licitacion' => ['identificador' => $f->identificador, 'titulo' => $f->titulo, 'importe_total' => (float) $f->importe_total],
                    'tipo_procedimiento' => $f->tipo_procedimiento,
                    'importe_adjudicado' => (float) $f->importe,
                    'organismo' => $f->organismo,
                    'empresa' => $f->empresa,
                ],
                entidades: $entidades,
            );
        }
    }
}
