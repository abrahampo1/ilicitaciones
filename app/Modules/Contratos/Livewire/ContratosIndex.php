<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Licitacion;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
#[Title('Contratos - I-Licitaciones')]
class ContratosIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $procedimiento = '';

    #[Url]
    public string $ccaa = '';

    #[Url]
    public string $importeMin = '';

    #[Url]
    public string $importeMax = '';

    #[Url]
    public string $sort = 'fecha_actualizacion';

    #[Url]
    public string $dir = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingTipo(): void
    {
        $this->resetPage();
    }

    public function updatingProcedimiento(): void
    {
        $this->resetPage();
    }

    public function updatingCcaa(): void
    {
        $this->resetPage();
    }

    public function updatingImporteMin(): void
    {
        $this->resetPage();
    }

    public function updatingImporteMax(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'tipo', 'procedimiento', 'ccaa', 'importeMin', 'importeMax']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Licitacion::query()->with('organismo:id,nombre');

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($w) use ($q) {
                $w->where('titulo', 'like', "%{$q}%")
                  ->orWhere('expediente', 'like', "%{$q}%")
                  ->orWhere('adjudicatario_nombre', 'like', "%{$q}%")
                  ->orWhere('adjudicatario_nif', 'like', "%{$q}%");
            });
        }

        if ($this->status) {
            $query->status($this->status);
        }

        if ($this->tipo) {
            $query->tipo($this->tipo);
        }

        if ($this->procedimiento) {
            $query->procedimiento($this->procedimiento);
        }

        if ($this->ccaa) {
            $query->where('comunidad_autonoma', $this->ccaa);
        }

        if ($this->importeMin !== '') {
            $query->importeMin((float) $this->importeMin);
        }

        if ($this->importeMax !== '') {
            $query->importeMax((float) $this->importeMax);
        }

        $allowedSorts = ['fecha_actualizacion', 'importe_con_iva', 'importe_total', 'fecha_adjudicacion', 'expediente'];
        $sortField = in_array($this->sort, $allowedSorts) ? $this->sort : 'fecha_actualizacion';
        $query->orderBy($sortField, $this->dir === 'asc' ? 'asc' : 'desc');

        $licitaciones = $query->paginate(20);

        $comunidades = cache()->remember('contratos_ccaa_list', 3600, fn() =>
            Licitacion::whereNotNull('comunidad_autonoma')
                ->distinct()
                ->pluck('comunidad_autonoma')
                ->sort()
                ->values()
        );

        return view('livewire.contratos.contratos-index', [
            'licitaciones' => $licitaciones,
            'statusLabels' => Licitacion::STATUS_LABELS,
            'tipoLabels' => Licitacion::TIPO_LABELS,
            'procedimientoLabels' => Licitacion::PROCEDIMIENTO_LABELS,
            'comunidades' => $comunidades,
        ]);
    }
}
