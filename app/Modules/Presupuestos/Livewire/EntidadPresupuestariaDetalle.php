<?php

namespace Modules\Presupuestos\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

#[Layout('layouts.app')]
class EntidadPresupuestariaDetalle extends Component
{
    public EntidadPresupuestaria $entidad;

    #[Url]
    public string $tab = 'gastos';

    #[Url]
    public string $ejercicio = '';

    public function mount(int $id): void
    {
        $this->entidad = EntidadPresupuestaria::findOrFail($id);

        if (!$this->ejercicio) {
            $this->ejercicio = (string) (PartidaPresupuestaria::where('entidad_id', $id)
                ->max('ejercicio') ?? date('Y'));
        }
    }

    public function render()
    {
        $ejercicioInt = (int) $this->ejercicio;
        $tipoPresupuesto = $this->tab === 'ejecucion' ? 'gastos' : $this->tab;

        // Desglose por capítulo
        $porCapitulo = DB::table('partidas_presupuestarias')
            ->select(
                DB::raw('LEFT(codigo_economica, 1) as capitulo'),
                DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total')
            )
            ->where('entidad_id', $this->entidad->id)
            ->where('ejercicio', $ejercicioInt)
            ->where('tipo_presupuesto', $tipoPresupuesto)
            ->whereNotNull('codigo_economica')
            ->groupBy(DB::raw('LEFT(codigo_economica, 1)'))
            ->orderBy('capitulo')
            ->get();

        $totalPresupuesto = $porCapitulo->sum('total');

        // Ejercicios disponibles
        $ejercicios = PartidaPresupuestaria::where('entidad_id', $this->entidad->id)
            ->distinct()
            ->orderByDesc('ejercicio')
            ->pluck('ejercicio');

        // Ejecución (si tab = ejecucion)
        $ejecucion = [];
        if ($this->tab === 'ejecucion') {
            $ejecucion = DB::table('ejecucion_presupuestaria as ej')
                ->join('partidas_presupuestarias as p', 'ej.partida_id', '=', 'p.id')
                ->select(
                    'ej.periodo',
                    DB::raw('SUM(ej.obligaciones) as obligaciones'),
                    DB::raw('SUM(ej.pagos_realizados) as pagos'),
                    DB::raw('SUM(ej.credito_autorizado) as autorizado')
                )
                ->where('p.entidad_id', $this->entidad->id)
                ->where('p.ejercicio', $ejercicioInt)
                ->groupBy('ej.periodo')
                ->orderBy('ej.periodo')
                ->get();
        }

        $capituloLabels = $tipoPresupuesto === 'ingresos'
            ? ClasificacionPresupuestaria::CAPITULOS_INGRESOS
            : ClasificacionPresupuestaria::CAPITULOS_GASTOS;

        // Cross-link con Contratos si tiene dir3
        $organismoContratos = null;
        if ($this->entidad->codigo_dir3) {
            $organismoContratos = \Modules\Contratos\Models\Organismo::where('dir3_code', $this->entidad->codigo_dir3)->first();
        }

        return view('livewire.presupuestos.entidad-detalle', compact(
            'porCapitulo', 'totalPresupuesto', 'ejercicios', 'ejecucion',
            'capituloLabels', 'organismoContratos'
        ));
    }
}
