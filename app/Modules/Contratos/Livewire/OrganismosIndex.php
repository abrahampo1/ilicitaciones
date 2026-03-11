<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Categoria;
use Modules\Contratos\Models\Licitacion;
use Modules\Contratos\Models\Organismo;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
#[Title('Organismos - I-Licitaciones')]
class OrganismosIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $provincia = '';

    #[Url]
    public string $categoriaId = '';

    #[Url]
    public string $importeMin = '';

    #[Url]
    public string $importeMax = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProvincia(): void
    {
        $this->resetPage();
    }

    public function updatingCategoriaId(): void
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'provincia', 'categoriaId', 'importeMin', 'importeMax']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Organismo::query();

        if ($this->search) {
            $query->where('nombre', 'like', "%{$this->search}%");
        }

        if ($this->provincia) {
            $query->where('provincia', $this->provincia);
        }

        $baseQuery = $query
            ->select('organismos.*')
            ->withCount('licitaciones')
            ->withSum('licitaciones as total_importe', 'importe_total');

        if ($this->importeMin !== '') {
            $baseQuery->having('total_importe', '>=', (float) $this->importeMin);
        }

        if ($this->importeMax !== '') {
            $baseQuery->having('total_importe', '<=', (float) $this->importeMax);
        }

        if ($this->categoriaId) {
            $baseQuery->whereHas('licitaciones', function ($q) {
                $q->where('categoria_id', $this->categoriaId);
            });
        }

        $organismos = $baseQuery->orderByDesc('total_importe')->paginate(15);

        $totalOrganismos = cache()->remember('organismos_count', 3600, fn() => Organismo::count());
        $totalVolumen = cache()->remember('licitaciones_sum_total', 3600, fn() => Licitacion::sum('importe_total'));

        $provincias = cache()->remember('organismos_provincias', 3600, fn() =>
            Organismo::select('provincia')
                ->distinct()
                ->whereNotNull('provincia')
                ->where('provincia', '!=', '')
                ->orderBy('provincia')
                ->pluck('provincia')
        );

        $categorias = cache()->remember('categorias_list', 3600, fn() => Categoria::orderBy('nombre')->get());

        return view('livewire.contratos.organismos-index', compact(
            'organismos', 'totalOrganismos', 'totalVolumen', 'provincias', 'categorias'
        ));
    }
}
