<?php

namespace Modules\Presupuestos\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

#[Layout('layouts.app')]
#[Title('Presupuestos Públicos - I-Licitaciones')]
class PresupuestosDashboard extends Component
{
    public function render()
    {
        $stats = cache()->remember('presupuestos_stats', 3600, function () {
            $totalPresupuestado = PartidaPresupuestaria::gastos()
                ->sum(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)'));

            return [
                'totalPresupuestado' => $totalPresupuestado,
                'totalEntidades' => EntidadPresupuestaria::count(),
                'totalPartidas' => PartidaPresupuestaria::count(),
                'ejerciciosCount' => PartidaPresupuestaria::distinct()->count('ejercicio'),
            ];
        });

        $topEntidades = cache()->remember('presupuestos_top_entidades', 3600, function () {
            return DB::table('partidas_presupuestarias')
                ->select('entidad_id', DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total'))
                ->where('tipo_presupuesto', 'gastos')
                ->groupBy('entidad_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->entidad = EntidadPresupuestaria::select('id', 'nombre', 'tipo')->find($row->entidad_id);
                    return $row;
                });
        });

        $porCapitulo = cache()->remember('presupuestos_por_capitulo', 3600, function () {
            return DB::table('partidas_presupuestarias')
                ->select(
                    DB::raw('LEFT(codigo_economica, 1) as capitulo'),
                    DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total')
                )
                ->where('tipo_presupuesto', 'gastos')
                ->whereNotNull('codigo_economica')
                ->groupBy(DB::raw('LEFT(codigo_economica, 1)'))
                ->orderBy('capitulo')
                ->get();
        });

        $ejercicios = cache()->remember('presupuestos_ejercicios', 3600, function () {
            return PartidaPresupuestaria::distinct()
                ->orderByDesc('ejercicio')
                ->pluck('ejercicio');
        });

        return view('livewire.presupuestos.dashboard', compact('stats', 'topEntidades', 'porCapitulo', 'ejercicios'));
    }
}
