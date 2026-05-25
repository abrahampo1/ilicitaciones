<?php

namespace App\Analysis;

use App\Analysis\Detectors\AdjudicatarioUnicoDetector;
use App\Analysis\Detectors\ConcentracionDetector;
use App\Analysis\Detectors\Detector;
use App\Analysis\Detectors\InformeRegionalDetector;
use App\Analysis\Detectors\InformeSectorialDetector;
use App\Analysis\Detectors\PerfilDetector;
use App\Analysis\Detectors\RankingDetector;
use App\Analysis\Detectors\SinCompetenciaDetector;
use App\Analysis\Detectors\SobrecosteDetector;
use App\Analysis\Detectors\UrgenciaDetector;

class DetectorRegistry
{
    /** Todos los detectores conocidos (tipo => clase). */
    private const MAPA = [
        'adjudicatario_unico' => AdjudicatarioUnicoDetector::class,
        'concentracion' => ConcentracionDetector::class,
        'urgencia' => UrgenciaDetector::class,
        'sobrecoste' => SobrecosteDetector::class,
        'sin_competencia' => SinCompetenciaDetector::class,
        'ranking' => RankingDetector::class,
        'informe_sectorial' => InformeSectorialDetector::class,
        'informe_regional' => InformeRegionalDetector::class,
        'perfil' => PerfilDetector::class,
    ];

    /** @return list<Detector> */
    public function activos(): array
    {
        $activos = (array) config('periodico.detectores_activos', array_keys(self::MAPA));

        return collect($activos)
            ->filter(fn ($tipo) => isset(self::MAPA[$tipo]))
            ->map(fn ($tipo) => app(self::MAPA[$tipo]))
            ->values()
            ->all();
    }
}
