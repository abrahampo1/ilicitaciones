<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Http\Request;

class OrganismoController extends Controller
{
    public function index(Request $request)
    {
        $query = Organismo::query();

        if ($search = $request->input('search')) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        if ($request->has('provincia') && $request->input('provincia') !== '') {
            $query->where('provincia', $request->input('provincia'));
        }

        $filterHash = md5(json_encode(array_filter($request->only(['page', 'search', 'provincia', 'importe_min', 'importe_max', 'categoria_id']))));
        $cacheKey = "organismos_{$filterHash}";

        $organismos = cache()->remember($cacheKey, 3600, function () use ($query, $request) {
            $baseQuery = $query
                ->select('organismos.*')
                ->withCount('licitaciones')
                ->withSum('licitaciones as total_importe', 'importe_total');

            if ($request->has('importe_min') && $request->input('importe_min') !== '') {
                $baseQuery->having('total_importe', '>=', (float) $request->input('importe_min'));
            }
            if ($request->has('importe_max') && $request->input('importe_max') !== '') {
                $baseQuery->having('total_importe', '<=', (float) $request->input('importe_max'));
            }

            if ($request->has('categoria_id') && $request->input('categoria_id') !== '') {
                $baseQuery->whereHas('licitaciones', function ($q) use ($request) {
                    $q->where('categoria_id', $request->input('categoria_id'));
                });
            }

            return $baseQuery
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

        $categorias = cache()->remember('categorias_list', 3600, fn () => Categoria::orderBy('nombre')->get());

        return view('organismos', compact('organismos', 'totalOrganismos', 'totalVolumen', 'provincias', 'categorias'));
    }

    public function show($id)
    {
        $organismo = Organismo::findOrFail($id);

        $showData = cache()->remember("organismo_{$id}", 1800, function () use ($organismo) {
            $totalLicitaciones = $organismo->licitaciones()->count();
            $totalImporte = $organismo->licitaciones()->sum('importe_total');
            $licitaciones = $organismo->licitaciones()->latest('fecha_actualizacion')->limit(20)->get();

            $inversionAnual = $organismo->licitaciones()
                ->selectRaw('YEAR(fecha_actualizacion) as year, SUM(importe_total) as total')
                ->whereNotNull('fecha_actualizacion')
                ->groupBy('year')
                ->orderByDesc('year')
                ->get();

            $maxYearlyTotal = $inversionAnual->max('total');

            return compact('totalLicitaciones', 'totalImporte', 'licitaciones', 'inversionAnual', 'maxYearlyTotal');
        });

        return view('organismo', array_merge(['organismo' => $organismo], $showData));
    }
}
