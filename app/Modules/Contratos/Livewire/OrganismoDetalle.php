<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Organismo;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class OrganismoDetalle extends Component
{
    public Organismo $organismo;

    public function mount(int $id): void
    {
        $this->organismo = Organismo::findOrFail($id);
    }

    public function render()
    {
        $totalLicitaciones = $this->organismo->licitaciones()->count();
        $totalImporte = $this->organismo->licitaciones()->sum('importe_total');
        $licitaciones = $this->organismo->licitaciones()->latest('fecha_actualizacion')->limit(20)->get();

        $inversionAnual = $this->organismo->licitaciones()
            ->selectRaw('YEAR(fecha_actualizacion) as year, SUM(importe_total) as total')
            ->whereNotNull('fecha_actualizacion')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();

        $maxYearlyTotal = $inversionAnual->max('total');

        return view('livewire.contratos.organismo-detalle', compact(
            'totalLicitaciones', 'totalImporte', 'licitaciones', 'inversionAnual', 'maxYearlyTotal'
        ));
    }
}
