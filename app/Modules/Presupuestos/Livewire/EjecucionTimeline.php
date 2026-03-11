<?php

namespace Modules\Presupuestos\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

#[Layout('layouts.app')]
#[Title('Ejecución Presupuestaria - I-Licitaciones')]
class EjecucionTimeline extends Component
{
    #[Url]
    public string $entidadId = '';

    #[Url]
    public string $ejercicio = '';

    public function render()
    {
        $entidades = EntidadPresupuestaria::orderBy('nombre')->get(['id', 'nombre', 'tipo']);

        $timeline = collect();
        $totalAprobado = 0;
        $totalObligaciones = 0;
        $totalPagos = 0;

        if ($this->entidadId && $this->ejercicio) {
            $timeline = DB::table('ejecucion_presupuestaria as ej')
                ->join('partidas_presupuestarias as p', 'ej.partida_id', '=', 'p.id')
                ->select(
                    'ej.periodo',
                    DB::raw('SUM(ej.credito_autorizado) as autorizado'),
                    DB::raw('SUM(ej.obligaciones) as obligaciones'),
                    DB::raw('SUM(ej.pagos_realizados) as pagos'),
                    DB::raw('SUM(ej.remanentes) as remanentes'),
                    DB::raw('AVG(ej.porcentaje_ejecucion) as pct_ejecucion')
                )
                ->where('p.entidad_id', $this->entidadId)
                ->where('p.ejercicio', $this->ejercicio)
                ->groupBy('ej.periodo')
                ->orderBy('ej.periodo')
                ->get();

            $totalAprobado = PartidaPresupuestaria::where('entidad_id', $this->entidadId)
                ->where('ejercicio', $this->ejercicio)
                ->gastos()
                ->sum(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)'));

            $totalObligaciones = $timeline->sum('obligaciones');
            $totalPagos = $timeline->sum('pagos');
        }

        $ejercicios = cache()->remember('ejecucion_ejercicios', 3600, fn() =>
            PartidaPresupuestaria::distinct()->orderByDesc('ejercicio')->pluck('ejercicio')
        );

        return view('livewire.presupuestos.ejecucion', compact(
            'entidades', 'timeline', 'ejercicios',
            'totalAprobado', 'totalObligaciones', 'totalPagos'
        ));
    }
}
