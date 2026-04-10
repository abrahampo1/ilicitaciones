<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
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
                ->select('empresas.id as empresa_id', 'empresas.nombre', DB::raw('SUM(adjudicacions.importe) as total_importe'))
                ->groupBy('empresas.id', 'empresas.nombre')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get();
        });

        $topOrganismos = cache()->remember('home_top_organismos', 3600, function () {
            return DB::table('licitacions')
                ->join('organismos', 'licitacions.organismo_id', '=', 'organismos.id')
                ->select('organismos.id as organismo_id', 'organismos.nombre', DB::raw('SUM(licitacions.importe_total) as total_importe'))
                ->groupBy('organismos.id', 'organismos.nombre')
                ->orderByDesc('total_importe')
                ->limit(10)
                ->get();
        });

        $ultimasLicitaciones = cache()->remember('home_ultimas_licitaciones', 300, function () {
            return Licitacion::select('id', 'titulo', 'importe_total', 'estado', 'fecha_actualizacion', 'organismo_id')
                ->with('organismo:id,nombre')
                ->orderByDesc('fecha_actualizacion')
                ->limit(8)
                ->get();
        });

        return view('home', compact('stats', 'topEmpresas', 'topOrganismos', 'ultimasLicitaciones'));
    }
}
