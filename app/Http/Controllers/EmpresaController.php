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
        $categoriaId = $request->input('categoria_id');
        $tieneCategoria = $categoriaId !== null && $categoriaId !== '';

        // Camino rápido: lee columnas precomputadas (total_importe indexado).
        // Solo el filtro por categoría necesita el GROUP BY en vivo.
        if ($tieneCategoria) {
            $query = $this->categoriaQuery($request, $categoriaId);
        } else {
            $query = $this->fastQuery($request);
        }

        $filterHash = md5(json_encode(array_filter($request->only(['page', 'search', 'importe_min', 'importe_max', 'categoria_id']))));
        $cacheKey = "empresas_{$filterHash}";

        $empresas = cache()->remember($cacheKey, 3600, function () use ($query) {
            return $query->orderByDesc('total_importe')->paginate(15);
        });

        $empresas->withQueryString();

        $totalVolumen = cache()->remember('adjudicaciones_sum_total', 3600, fn () => Adjudicacion::sum('importe'));
        $totalEmpresas = cache()->remember('empresas_count', 3600, fn () => Empresa::count());
        $categorias = cache()->remember('categorias_list', 3600, fn () => Categoria::whereIn('id', function ($q) {
            $q->select('categoria_id')->from('licitacions')->whereNotNull('categoria_id');
        })->orderBy('nombre')->get(['id', 'nombre']));

        return view('empresas', compact('empresas', 'totalVolumen', 'totalEmpresas', 'categorias'));
    }

    private function fastQuery(Request $request)
    {
        $query = DB::table('empresas')
            ->select('empresas.id', 'empresas.nombre', 'empresas.identificador', 'empresas.total_importe', 'empresas.total_adjudicaciones')
            ->where('empresas.total_adjudicaciones', '>', 0);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('empresas.nombre', 'like', "%{$search}%")
                    ->orWhere('empresas.identificador', 'like', "%{$search}%");
            });
        }

        if ($request->filled('importe_min')) {
            $query->where('empresas.total_importe', '>=', (float) $request->input('importe_min'));
        }
        if ($request->filled('importe_max')) {
            $query->where('empresas.total_importe', '<=', (float) $request->input('importe_max'));
        }

        return $query;
    }

    private function categoriaQuery(Request $request, $categoriaId)
    {
        $query = DB::table('adjudicacions')
            ->join('empresas', 'adjudicacions.empresa_id', '=', 'empresas.id')
            ->join('licitacions', 'adjudicacions.licitacion_id', '=', 'licitacions.id')
            ->where('licitacions.categoria_id', $categoriaId)
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

        if ($request->filled('importe_min')) {
            $query->havingRaw('SUM(adjudicacions.importe) >= ?', [(float) $request->input('importe_min')]);
        }
        if ($request->filled('importe_max')) {
            $query->havingRaw('SUM(adjudicacions.importe) <= ?', [(float) $request->input('importe_max')]);
        }

        return $query;
    }

    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);

        $showData = cache()->remember("empresa_{$id}", 1800, function () use ($empresa) {
            // Totales desde columnas precomputadas (sin SUM/COUNT en request).
            $totalImporte = (float) $empresa->total_importe;
            $totalAdjudicaciones = (int) $empresa->total_adjudicaciones;
            $importeMedio = $totalAdjudicaciones > 0 ? $totalImporte / $totalAdjudicaciones : 0;

            $adjudicaciones = Adjudicacion::where('empresa_id', $empresa->id)
                ->with('licitacion.organismo')
                ->orderByDesc('fecha_adjudicacion')
                ->limit(50)
                ->get();

            // Serie anual precalculada en inversiones_anuales.
            $inversionAnual = DB::table('inversiones_anuales')
                ->where('entity_type', 'empresa')
                ->where('entity_id', $empresa->id)
                ->orderByDesc('year')
                ->get(['year', 'total']);

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
