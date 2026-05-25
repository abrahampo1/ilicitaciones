<?php

namespace App\Analysis\Detectors;

use App\Analysis\Scoring;
use App\Analysis\StoryCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Perfil/investigación de empresas y organismos de gran volumen. Adjunta la serie
 * anual precomputada (inversiones_anuales) al payload.
 */
class PerfilDetector implements Detector
{
    public function tipo(): string
    {
        return 'perfil';
    }

    public function detect(): iterable
    {
        $min = (float) config('periodico.umbrales.perfil_importe_min');
        $year = now()->year;

        $empresas = DB::table('empresas')
            ->where('total_importe', '>=', $min)
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get(['id', 'nombre', 'identificador', 'total_importe', 'total_adjudicaciones']);

        foreach ($empresas as $e) {
            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'perfiles',
                signature: StoryCandidate::firma('perfil', 'empresa:'.$e->id, 'year:'.$year),
                score: Scoring::importe((float) $e->total_importe, $min, 50, 80),
                payload: [
                    'tipo' => 'perfil',
                    'entidad' => 'empresa',
                    'nombre' => $e->nombre,
                    'identificador' => $e->identificador,
                    'total_importe' => (float) $e->total_importe,
                    'total_adjudicaciones' => (int) $e->total_adjudicaciones,
                    'serie_anual' => $this->serie('empresa', $e->id),
                ],
                entidades: [['type' => 'empresa', 'id' => (int) $e->id, 'primary' => true]],
            );
        }

        $organismos = DB::table('organismos')
            ->where('total_importe', '>=', $min)
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get(['id', 'nombre', 'provincia', 'total_importe', 'total_licitaciones']);

        foreach ($organismos as $o) {
            yield new StoryCandidate(
                tipo: $this->tipo(),
                seccion: 'perfiles',
                signature: StoryCandidate::firma('perfil', 'organismo:'.$o->id, 'year:'.$year),
                score: Scoring::importe((float) $o->total_importe, $min, 50, 80),
                payload: [
                    'tipo' => 'perfil',
                    'entidad' => 'organismo',
                    'nombre' => $o->nombre,
                    'provincia' => $o->provincia,
                    'total_importe' => (float) $o->total_importe,
                    'total_licitaciones' => (int) $o->total_licitaciones,
                    'serie_anual' => $this->serie('organismo', $o->id),
                ],
                entidades: [['type' => 'organismo', 'id' => (int) $o->id, 'primary' => true]],
            );
        }
    }

    /** @return list<array{year:int,importe:float}> */
    private function serie(string $tipo, int $id): array
    {
        return DB::table('inversiones_anuales')
            ->where('entity_type', $tipo)->where('entity_id', $id)
            ->orderBy('year')
            ->get(['year', 'total'])
            ->map(fn ($r) => ['year' => (int) $r->year, 'importe' => (float) $r->total])
            ->all();
    }
}
