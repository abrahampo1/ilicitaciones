<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Adjudicacion;
use Modules\Contratos\Models\Categoria;
use Modules\Contratos\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
#[Title('Empresas - I-Licitaciones')]
class EmpresasIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

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
        $this->reset(['search', 'categoriaId', 'importeMin', 'importeMax']);
        $this->resetPage();
    }

    public function render()
    {
        $query = DB::table('adjudicacions')
            ->join('empresas', 'adjudicacions.empresa_id', '=', 'empresas.id')
            ->leftJoin('licitacions', 'adjudicacions.licitacion_id', '=', 'licitacions.id')
            ->select(
                'empresas.id',
                'empresas.nombre',
                'empresas.identificador',
                DB::raw('SUM(adjudicacions.importe) as total_importe'),
                DB::raw('COUNT(adjudicacions.id) as total_adjudicaciones')
            )
            ->groupBy('empresas.id', 'empresas.nombre', 'empresas.identificador');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('empresas.nombre', 'like', "%{$search}%")
                  ->orWhere('empresas.identificador', 'like', "%{$search}%");
            });
        }

        if ($this->importeMin !== '') {
            $query->havingRaw('SUM(adjudicacions.importe) >= ?', [(float) $this->importeMin]);
        }

        if ($this->importeMax !== '') {
            $query->havingRaw('SUM(adjudicacions.importe) <= ?', [(float) $this->importeMax]);
        }

        if ($this->categoriaId) {
            $query->where('licitacions.categoria_id', $this->categoriaId);
        }

        $empresas = $query->orderByDesc('total_importe')->paginate(15);

        $totalVolumen = cache()->remember('adjudicaciones_sum_total', 3600, fn() => Adjudicacion::sum('importe'));
        $totalEmpresas = cache()->remember('empresas_count', 3600, fn() => Empresa::count());
        $categorias = cache()->remember('categorias_list', 3600, fn() => Categoria::orderBy('nombre')->get());

        return view('livewire.contratos.empresas-index', compact(
            'empresas', 'totalVolumen', 'totalEmpresas', 'categorias'
        ));
    }
}
