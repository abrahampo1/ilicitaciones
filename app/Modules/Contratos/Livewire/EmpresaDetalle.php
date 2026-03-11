<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Adjudicacion;
use Modules\Contratos\Models\Empresa;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class EmpresaDetalle extends Component
{
    public Empresa $empresa;

    public function mount(int $id): void
    {
        $this->empresa = Empresa::findOrFail($id);
    }

    public function render()
    {
        $adjudicacionesQuery = Adjudicacion::where('empresa_id', $this->empresa->id);

        $totalImporte = (clone $adjudicacionesQuery)->sum('importe');
        $totalAdjudicaciones = (clone $adjudicacionesQuery)->count();
        $importeMedio = $totalAdjudicaciones > 0 ? $totalImporte / $totalAdjudicaciones : 0;

        $adjudicaciones = Adjudicacion::where('empresa_id', $this->empresa->id)
            ->with('licitacion.organismo')
            ->orderByDesc('fecha_adjudicacion')
            ->limit(50)
            ->get();

        $inversionAnual = Adjudicacion::where('empresa_id', $this->empresa->id)
            ->selectRaw('YEAR(fecha_adjudicacion) as year, SUM(importe) as total')
            ->whereNotNull('fecha_adjudicacion')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();

        $maxYearlyTotal = $inversionAnual->max('total');

        $relatedCompanies = Empresa::where('identificador', $this->empresa->identificador)
            ->whereNot('id', $this->empresa->id)
            ->get();

        return view('livewire.contratos.empresa-detalle', compact(
            'totalImporte', 'totalAdjudicaciones', 'importeMedio',
            'adjudicaciones', 'inversionAnual', 'maxYearlyTotal', 'relatedCompanies'
        ));
    }
}
