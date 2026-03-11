<?php

namespace Modules\Presupuestos\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Services\BudgetAggregator;

#[Layout('layouts.app')]
#[Title('Comparador Municipal - I-Licitaciones')]
class ComparadorMunicipal extends Component
{
    #[Url]
    public string $municipio1 = '';

    #[Url]
    public string $municipio2 = '';

    #[Url]
    public string $ejercicio = '';

    public function render()
    {
        $municipios = EntidadPresupuestaria::tipo(EntidadPresupuestaria::TIPO_MUNICIPIO)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_ine', 'poblacion']);

        $comparacion = null;
        if ($this->municipio1 && $this->municipio2 && $this->ejercicio) {
            $aggregator = new BudgetAggregator();
            $comparacion = $aggregator->compararPerCapita(
                (int) $this->municipio1,
                (int) $this->municipio2,
                (int) $this->ejercicio
            );
        }

        $ejercicios = cache()->remember('comparador_ejercicios', 3600, fn() =>
            \Modules\Presupuestos\Models\PartidaPresupuestaria::distinct()
                ->orderByDesc('ejercicio')
                ->pluck('ejercicio')
        );

        $capituloLabels = ClasificacionPresupuestaria::CAPITULOS_GASTOS;

        return view('livewire.presupuestos.comparador', compact(
            'municipios', 'comparacion', 'ejercicios', 'capituloLabels'
        ));
    }
}
