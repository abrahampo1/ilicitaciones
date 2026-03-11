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
                ->select('empresa_id', DB::raw('SUM(importe) as total_importe'))
                ->groupBy('empresa_id')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->empresa = Empresa::select('id', 'nombre')->find($row->empresa_id);
                    return $row;
                });
        });

        $topOrganismos = cache()->remember('home_top_organismos', 3600, function () {
            return DB::table('licitacions')
                ->select('organismo_id', DB::raw('SUM(importe_total) as total_importe'))
                ->groupBy('organismo_id')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $row->organismo = Organismo::select('id', 'nombre')->find($row->organismo_id);
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
