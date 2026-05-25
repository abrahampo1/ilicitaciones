<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Licitación relevante con un único adjudicatario (una sola adjudicación).
 */
class AdjudicatarioUnicoDetector implements Detector
{
    public function tipo(): string
    {
        return 'adjudicatario_unico';
    }

    public function detect(): iterable
    {
        $umbral = (float) config('periodico.umbrales.adjudicatario_unico_importe');
        $cutoff = now()->subDays((int) config('periodico.ventana_dias'))->toDateTimeString();
        $codigosSinComp = (array) config('periodico.umbrales.sin_competencia_codigos');
        $codigosUrgentes = (array) config('periodico.umbrales.urgencia_codigos');

        $filas = DB::table('licitacions as l')
            ->join('adjudicacions as a', 'a.licitacion_id', '=', 'l.id')
            ->leftJoin('organismos as o', 'o.id', '=', 'l.organismo_id')
            ->leftJoin('empresas as e', 'e.id', '=', 'a.empresa_id')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereIn('l.id', function ($q) {
                $q->select('licitacion_id')->from('adjudicacions')
                    ->groupBy('licitacion_id')->havingRaw('COUNT(*) = 1');
            })
            ->where('l.importe_total', '>=', $umbral)
            ->where('l.fecha_actualizacion', '>=', $cutoff)
            ->orderByDesc('l.importe_total')
            ->limit(200)
            ->get([
                'l.id as licitacion_id', 'l.identificador', 'l.titulo', 'l.importe_total',
                'a.importe', 'a.tipo_procedimiento', 'a.urgencia',
                'o.id as organismo_id', 'o.nombre as organismo', 'o.provincia',
                'e.id as empresa_id', 'e.nombre as empresa', 'c.nombre as cpv',
            ]);

        foreach ($filas as $f) {
            $score = Scoring::importe((float) $f->importe_total, $umbral, 40, 50)
                + (in_array((string) $f->tipo_procedimiento, $codigosSinComp, true) ? 20 : 0)
                + (in_array((string) $f->urgencia, $codigosUrgentes, true) ? 15 : 0);

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
                signature: StoryCandidate::firma('adjudicatario_unico', (string) $f->identificador),
                score: $score,
                payload: [
                    'tipo' => 'adjudicatario_unico',
                    'licitacion' => ['identificador' => $f->identificador, 'titulo' => $f->titulo, 'importe_total' => (float) $f->importe_total],
                    'organismo' => $f->organismo,
                    'provincia' => $f->provincia,
                    'empresa' => $f->empresa,
                    'cpv' => $f->cpv,
                    'importe_adjudicado' => (float) $f->importe,
                    'tipo_procedimiento' => $f->tipo_procedimiento,
                ],
                entidades: $entidades,
            );
        }
    }
}
