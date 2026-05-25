<?php

namespace App\Http\Controllers;

use App\Jobs\RecalcularEstadisticas;
use App\Models\Licitacion;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        // Stats globales y tops salen de la tabla `estadisticas` (precalculada por
        // el job). Si aún no existe (primer arranque), se calcula una vez y se encola
        // el job para los siguientes; nunca se hace GROUP BY pesado en el request.
        $stats = $this->fromEstadisticas('home_stats', true);
        $topEmpresas = $this->fromEstadisticas('home_top_empresas');
        $topOrganismos = $this->fromEstadisticas('home_top_organismos');

        $ultimasLicitaciones = cache()->remember('home_ultimas_licitaciones', 300, function () {
            return Licitacion::select('id', 'titulo', 'importe_total', 'estado', 'fecha_actualizacion', 'organismo_id')
                ->with('organismo:id,nombre')
                ->orderByDesc('fecha_actualizacion')
                ->limit(8)
                ->get();
        });

        return view('home', compact('stats', 'topEmpresas', 'topOrganismos', 'ultimasLicitaciones'));
    }

    private function fromEstadisticas(string $clave, bool $assoc = false)
    {
        $row = DB::table('estadisticas')->where('clave', $clave)->value('valor');

        if ($row !== null) {
            return json_decode($row, $assoc);
        }

        // Cold start: recalcular en cola y servir un valor mínimo sin bloquear.
        RecalcularEstadisticas::dispatch();

        if ($assoc) {
            return [
                'latestDate' => null,
                'totalImporte' => 0,
                'conteoLicitaciones' => 0,
                'totalOrganismos' => 0,
                'totalEmpresas' => 0,
                'totalVolumenAdjudicado' => 0,
            ];
        }

        return collect();
    }
}
