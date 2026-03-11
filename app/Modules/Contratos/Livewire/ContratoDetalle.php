<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Licitacion;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContratoDetalle extends Component
{
    public Licitacion $licitacion;

    public function mount(int $id): void
    {
        $this->licitacion = Licitacion::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.contratos.contrato-detalle', [
            'licitacion' => $this->licitacion,
        ]);
    }
}
