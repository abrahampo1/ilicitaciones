<?php

namespace Modules\Presupuestos\Services;

use Illuminate\Support\Facades\DB;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

class BudgetAggregator
{
    /**
     * Resumen por capítulo económico para una entidad y ejercicio.
     */
    public function porCapitulo(int $entidadId, int $ejercicio, string $tipoPresupuesto = 'gastos'): array
    {
        return DB::table('partidas_presupuestarias')
            ->select(
                DB::raw('LEFT(codigo_economica, 1) as capitulo'),
                DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total')
            )
            ->where('entidad_id', $entidadId)
            ->where('ejercicio', $ejercicio)
            ->where('tipo_presupuesto', $tipoPresupuesto)
            ->whereNotNull('codigo_economica')
            ->groupBy(DB::raw('LEFT(codigo_economica, 1)'))
            ->orderBy('capitulo')
            ->get()
            ->toArray();
    }

    /**
     * Totales por ejercicio para una entidad.
     */
    public function evolucionAnual(int $entidadId, string $tipoPresupuesto = 'gastos'): array
    {
        return DB::table('partidas_presupuestarias')
            ->select(
                'ejercicio',
                DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total')
            )
            ->where('entidad_id', $entidadId)
            ->where('tipo_presupuesto', $tipoPresupuesto)
            ->groupBy('ejercicio')
            ->orderBy('ejercicio')
            ->get()
            ->toArray();
    }

    /**
     * Comparación per cápita entre dos municipios.
     */
    public function compararPerCapita(int $entidad1Id, int $entidad2Id, int $ejercicio): array
    {
        $entidades = EntidadPresupuestaria::whereIn('id', [$entidad1Id, $entidad2Id])->get()->keyBy('id');
        $result = [];

        foreach ([$entidad1Id, $entidad2Id] as $entidadId) {
            $entidad = $entidades[$entidadId] ?? null;
            if (!$entidad) continue;

            $totalGastos = PartidaPresupuestaria::where('entidad_id', $entidadId)
                ->where('ejercicio', $ejercicio)
                ->gastos()
                ->sum(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)'));

            $totalIngresos = PartidaPresupuestaria::where('entidad_id', $entidadId)
                ->where('ejercicio', $ejercicio)
                ->ingresos()
                ->sum(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)'));

            $poblacion = $entidad->poblacion ?: 1;

            $result[$entidadId] = [
                'entidad' => $entidad,
                'gastos_total' => $totalGastos,
                'ingresos_total' => $totalIngresos,
                'gastos_per_capita' => round($totalGastos / $poblacion, 2),
                'ingresos_per_capita' => round($totalIngresos / $poblacion, 2),
                'capitulos' => $this->porCapitulo($entidadId, $ejercicio),
            ];
        }

        return $result;
    }

    /**
     * Stats generales del módulo.
     */
    public function statsGenerales(): array
    {
        return [
            'totalPresupuestado' => PartidaPresupuestaria::gastos()
                ->sum(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)')),
            'totalEntidades' => EntidadPresupuestaria::count(),
            'totalPartidas' => PartidaPresupuestaria::count(),
            'ejerciciosDisponibles' => PartidaPresupuestaria::distinct()
                ->pluck('ejercicio')
                ->sort()
                ->values()
                ->toArray(),
            'fuentesActivas' => PartidaPresupuestaria::distinct()
                ->pluck('fuente')
                ->toArray(),
        ];
    }
}
