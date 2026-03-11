<?php

namespace Modules\Contratos\Livewire;

use Modules\Contratos\Models\Empresa;
use Modules\Contratos\Models\Licitacion;
use Modules\Contratos\Models\Organismo;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('I-Licitaciones | Inteligencia de Mercado Público')]
class Dashboard extends Component
{
    public function render()
    {
        $stats = cache()->remember('home_stats', 3600, function () {
            return [
                'latestDate' => Licitacion::orderByDesc('fecha_actualizacion')->value('fecha_actualizacion'),
                'totalImporte' => Licitacion::sum('importe_total'),
                'conteoLicitaciones' => Licitacion::count(),
                'totalOrganismos' => Organismo::count(),
                'totalEmpresas' => Empresa::count(),
            ];
        });

        $topEmpresas = cache()->remember('home_top_empresas', 3600, function () {
            return DB::table('adjudicacions')
                ->join('empresas', 'adjudicacions.empresa_id', '=', 'empresas.id')
                ->select(
                    'empresas.id as empresa_id',
                    'empresas.nombre as empresa_nombre',
                    DB::raw('SUM(adjudicacions.importe) as total_importe')
                )
                ->groupBy('empresas.id', 'empresas.nombre')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->empresa = (object) ['id' => $row->empresa_id, 'nombre' => $row->empresa_nombre];
                    return $row;
                });
        });

        $topOrganismos = cache()->remember('home_top_organismos', 3600, function () {
            return DB::table('licitacions')
                ->join('organismos', 'licitacions.organismo_id', '=', 'organismos.id')
                ->select(
                    'organismos.id as organismo_id',
                    'organismos.nombre as organismo_nombre',
                    DB::raw('SUM(licitacions.importe_total) as total_importe')
                )
                ->whereNotNull('licitacions.organismo_id')
                ->groupBy('organismos.id', 'organismos.nombre')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->organismo = (object) ['id' => $row->organismo_id, 'nombre' => $row->organismo_nombre];
                    return $row;
                });
        });

        $ultimasLicitaciones = cache()->remember('home_ultimas_licitaciones', 300, function () {
            return Licitacion::select('id', 'titulo', 'importe_total', 'estado', 'status_code', 'fecha_actualizacion', 'organismo_id')
                ->with('organismo:id,nombre')
                ->orderByDesc('fecha_actualizacion')
                ->limit(8)
                ->get();
        });

        return view('livewire.contratos.dashboard', compact('stats', 'topEmpresas', 'topOrganismos', 'ultimasLicitaciones'));
    }
}
