<?php

namespace Modules\Presupuestos\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Modules\Presupuestos\Models\ClasificacionPresupuestaria;
use Modules\Presupuestos\Models\EntidadPresupuestaria;
use Modules\Presupuestos\Models\PartidaPresupuestaria;

#[Layout('layouts.app')]
#[Title('Explorador de Presupuestos - I-Licitaciones')]
class PresupuestosExplorador extends Component
{
    use WithPagination;

    #[Url]
    public string $ejercicio = '';

    #[Url]
    public string $entidadTipo = '';

    #[Url]
    public string $entidadId = '';

    #[Url]
    public string $clasificacion = 'economica';

    #[Url]
    public string $tipoPresupuesto = 'gastos';

    #[Url]
    public string $capitulo = '';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEjercicio(): void { $this->resetPage(); }
    public function updatingEntidadTipo(): void { $this->resetPage(); $this->entidadId = ''; }
    public function updatingEntidadId(): void { $this->resetPage(); }
    public function updatingClasificacion(): void { $this->resetPage(); }
    public function updatingTipoPresupuesto(): void { $this->resetPage(); }
    public function updatingCapitulo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'ejercicio', 'entidadTipo', 'entidadId', 'capitulo']);
        $this->resetPage();
    }

    public function render()
    {
        $query = PartidaPresupuestaria::query()->with('entidad:id,nombre,tipo');

        if ($this->ejercicio) {
            $query->ejercicio((int) $this->ejercicio);
        }

        if ($this->tipoPresupuesto) {
            $query->where('tipo_presupuesto', $this->tipoPresupuesto);
        }

        if ($this->entidadTipo) {
            $query->whereHas('entidad', fn($q) => $q->tipo($this->entidadTipo));
        }

        if ($this->entidadId) {
            $query->where('entidad_id', $this->entidadId);
        }

        if ($this->capitulo) {
            $query->capitulo($this->capitulo);
        }

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($w) use ($q) {
                $w->where('codigo_organica', 'like', "%{$q}%")
                  ->orWhere('codigo_funcional', 'like', "%{$q}%")
                  ->orWhere('codigo_economica', 'like', "%{$q}%")
                  ->orWhereHas('entidad', fn($e) => $e->where('nombre', 'like', "%{$q}%"));
            });
        }

        $query->orderByDesc(DB::raw('COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)'));

        $partidas = $query->paginate(25);

        // Datos para filtros
        $ejercicios = cache()->remember('explorador_ejercicios', 3600, fn() =>
            PartidaPresupuestaria::distinct()->orderByDesc('ejercicio')->pluck('ejercicio')
        );

        $entidades = $this->entidadTipo
            ? EntidadPresupuestaria::tipo($this->entidadTipo)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        // Resumen por capítulo para barras
        $resumenCapitulos = [];
        if ($this->ejercicio && ($this->entidadId || !$this->entidadTipo)) {
            $capQuery = PartidaPresupuestaria::query()
                ->select(
                    DB::raw('LEFT(codigo_economica, 1) as capitulo'),
                    DB::raw('SUM(COALESCE(credito_actual, credito_definitivo, credito_inicial, 0)) as total')
                )
                ->where('tipo_presupuesto', $this->tipoPresupuesto ?: 'gastos')
                ->whereNotNull('codigo_economica');

            if ($this->ejercicio) $capQuery->where('ejercicio', $this->ejercicio);
            if ($this->entidadId) $capQuery->where('entidad_id', $this->entidadId);

            $resumenCapitulos = $capQuery->groupBy(DB::raw('LEFT(codigo_economica, 1)'))
                ->orderBy('capitulo')
                ->get();
        }

        $capituloLabels = $this->tipoPresupuesto === 'ingresos'
            ? ClasificacionPresupuestaria::CAPITULOS_INGRESOS
            : ClasificacionPresupuestaria::CAPITULOS_GASTOS;

        return view('livewire.presupuestos.explorador', compact(
            'partidas', 'ejercicios', 'entidades', 'resumenCapitulos', 'capituloLabels'
        ));
    }
}
