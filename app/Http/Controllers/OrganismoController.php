<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganismoController extends Controller
{
    public function index(Request $request)
    {
        $query = Organismo::query();

        if ($search = $request->input('search')) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        if ($request->filled('provincia')) {
            $query->where('provincia', $request->input('provincia'));
        }

        $filterHash = md5(json_encode(array_filter($request->only(['page', 'search', 'provincia', 'importe_min', 'importe_max', 'categoria_id']))));
        $cacheKey = "organismos_{$filterHash}";

        $organismos = cache()->remember($cacheKey, 3600, function () use ($query, $request) {
            // total_importe / total_licitaciones son columnas precomputadas e indexadas:
            // sin withCount/withSum (subconsultas) en cada request.
            if ($request->filled('importe_min')) {
                $query->where('total_importe', '>=', (float) $request->input('importe_min'));
            }
            if ($request->filled('importe_max')) {
                $query->where('total_importe', '<=', (float) $request->input('importe_max'));
            }

            if ($request->filled('categoria_id')) {
                $query->whereHas('licitaciones', function ($q) use ($request) {
                    $q->where('categoria_id', $request->input('categoria_id'));
                });
            }

            return $query
                ->orderByDesc('total_importe')
                ->paginate(15);
        });

        $organismos->withQueryString();

        $totalOrganismos = cache()->remember('organismos_count', 3600, fn () => Organismo::count());
        $totalVolumen = cache()->remember('licitaciones_sum_total', 3600, fn () => Licitacion::sum('importe_total'));

        $provincias = cache()->remember('organismos_provincias', 3600, fn () => Organismo::select('provincia')
            ->distinct()
            ->whereNotNull('provincia')
            ->where('provincia', '!=', '')
            ->orderBy('provincia')
            ->pluck('provincia'));

        $categorias = cache()->remember('categorias_list', 3600, fn () => Categoria::whereIn('id', function ($q) {
            $q->select('categoria_id')->from('licitacions')->whereNotNull('categoria_id');
        })->orderBy('nombre')->get(['id', 'nombre']));

        return view('organismos', compact('organismos', 'totalOrganismos', 'totalVolumen', 'provincias', 'categorias'));
    }

    public function show($id)
    {
        $organismo = Organismo::findOrFail($id);

        $showData = cache()->remember("organismo_{$id}", 1800, function () use ($organismo) {
            // Totales desde columnas precomputadas.
            $totalLicitaciones = (int) $organismo->total_licitaciones;
            $totalImporte = (float) $organismo->total_importe;
            $licitaciones = $organismo->licitaciones()->latest('fecha_actualizacion')->limit(20)->get();

            // Serie anual precalculada.
            $inversionAnual = DB::table('inversiones_anuales')
                ->where('entity_type', 'organismo')
                ->where('entity_id', $organismo->id)
                ->orderByDesc('year')
                ->get(['year', 'total']);

            $maxYearlyTotal = $inversionAnual->max('total');

            return compact('totalLicitaciones', 'totalImporte', 'licitaciones', 'inversionAnual', 'maxYearlyTotal');
        });

        return view('organismo', array_merge(['organismo' => $organismo], $showData));
    }
}
