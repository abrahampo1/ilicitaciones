<?php

namespace App\Http\Controllers;

use App\Models\Adjudicacion;
use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('adjudicacions')
            ->join('empresas', 'adjudicacions.empresa_id', '=', 'empresas.id')
            ->select(
                'empresas.id',
                'empresas.nombre',
                'empresas.identificador',
                DB::raw('SUM(adjudicacions.importe) as total_importe'),
                DB::raw('COUNT(adjudicacions.id) as total_adjudicaciones')
            )
            ->groupBy('empresas.id', 'empresas.nombre', 'empresas.identificador');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('empresas.nombre', 'like', "%{$search}%")
                    ->orWhere('empresas.identificador', 'like', "%{$search}%");
            });
        }

        if ($request->has('importe_min') && $request->input('importe_min') !== '') {
            $query->havingRaw('SUM(adjudicacions.importe) >= ?', [(float) $request->input('importe_min')]);
        }
        if ($request->has('importe_max') && $request->input('importe_max') !== '') {
            $query->havingRaw('SUM(adjudicacions.importe) <= ?', [(float) $request->input('importe_max')]);
        }

        if ($request->has('categoria_id') && $request->input('categoria_id') !== '') {
            $query->leftJoin('licitacions', 'adjudicacions.licitacion_id', '=', 'licitacions.id')
                ->where('licitacions.categoria_id', $request->input('categoria_id'));
        }

        $filterHash = md5(json_encode(array_filter($request->only(['page', 'search', 'importe_min', 'importe_max', 'categoria_id']))));
        $cacheKey = "empresas_{$filterHash}";

        $empresas = cache()->remember($cacheKey, 3600, function () use ($query) {
            return $query->orderByDesc('total_importe')->paginate(15);
        });

        $empresas->withQueryString();

        $totalVolumen = cache()->remember('adjudicaciones_sum_total', 3600, fn () => Adjudicacion::sum('importe'));
        $totalEmpresas = cache()->remember('empresas_count', 3600, fn () => Empresa::count());
        $categorias = cache()->remember('categorias_list', 3600, fn () => Categoria::orderBy('nombre')->get());

        return view('empresas', compact('empresas', 'totalVolumen', 'totalEmpresas', 'categorias'));
    }

    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);

        $showData = cache()->remember("empresa_{$id}", 1800, function () use ($empresa) {
            $adjudicacionesQuery = Adjudicacion::where('empresa_id', $empresa->id);

            $totalImporte = (clone $adjudicacionesQuery)->sum('importe');
            $totalAdjudicaciones = (clone $adjudicacionesQuery)->count();
            $importeMedio = $totalAdjudicaciones > 0 ? $totalImporte / $totalAdjudicaciones : 0;

            $adjudicaciones = $adjudicacionesQuery
                ->with('licitacion.organismo')
                ->orderByDesc('fecha_adjudicacion')
                ->limit(50)
                ->get();

            $inversionAnual = Adjudicacion::where('empresa_id', $empresa->id)
                ->selectRaw('YEAR(fecha_adjudicacion) as year, SUM(importe) as total')
                ->whereNotNull('fecha_adjudicacion')
                ->groupBy('year')
                ->orderByDesc('year')
                ->get();

            $maxYearlyTotal = $inversionAnual->max('total');

            return compact('totalImporte', 'totalAdjudicaciones', 'importeMedio', 'adjudicaciones', 'inversionAnual', 'maxYearlyTotal');
        });

        $relatedCompanies = cache()->remember("empresa_related_{$empresa->identificador}", 3600, function () use ($empresa) {
            return Empresa::where('identificador', $empresa->identificador)
                ->whereNot('id', $empresa->id)
                ->get();
        });

        return view('empresa', array_merge(['empresa' => $empresa, 'relatedCompanies' => $relatedCompanies], $showData));
    }
}
