<?php

use App\Models\Licitacion;
use App\Models\Organismo;
use App\Models\Empresa;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Caché largo (1 hora) para estadísticas - se recalculan con comando artisan
    $stats = cache()->remember('home_stats', 3600, function () {
        // Queries simples sin agregaciones pesadas
        return [
            'latestDate' => Licitacion::orderByDesc('fecha_actualizacion')->value('fecha_actualizacion'),
            'totalImporte' => Licitacion::sum('importe_total'),
            'conteoLicitaciones' => Licitacion::count(),
            'totalOrganismos' => Organismo::count(),
            'totalEmpresas' => Empresa::count(),
        ];
    });

    // Top empresas usando query raw optimizada con índice
    $topEmpresas = cache()->remember('home_top_empresas', 3600, function () {
        return \Illuminate\Support\Facades\DB::table('adjudicacions')
            ->select('empresa_id', \Illuminate\Support\Facades\DB::raw('SUM(importe) as total_importe'))
            ->groupBy('empresa_id')
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->empresa = Empresa::select('id', 'nombre')->find($row->empresa_id);
                return $row;
            });
    });

    // Top organismos - usar subquery para evitar JOIN pesado
    $topOrganismos = cache()->remember('home_top_organismos', 3600, function () {
        return \Illuminate\Support\Facades\DB::table('licitacions')
            ->select('organismo_id', \Illuminate\Support\Facades\DB::raw('SUM(importe_total) as total_importe'))
            ->groupBy('organismo_id')
            ->orderByDesc('total_importe')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->organismo = Organismo::select('id', 'nombre')->find($row->organismo_id);
                return $row;
            });
    });

    // Últimas licitaciones - query simple con índice en fecha_actualizacion
    $ultimasLicitaciones = cache()->remember('home_ultimas_licitaciones', 300, function () {
        return Licitacion::select('id', 'titulo', 'importe_total', 'estado', 'fecha_actualizacion', 'organismo_id')
            ->with('organismo:id,nombre')
            ->orderByDesc('fecha_actualizacion')
            ->limit(8)
            ->get();
    });

    return view('home', compact('stats', 'topEmpresas', 'topOrganismos', 'ultimasLicitaciones'));
})->name('home');

Route::get('/licitacion/{id}', function () {
    return view('licitacion', [
        'licitacion' => Licitacion::find(request('id')),
    ]);
})->name('licitacion.show');

// Organismos
Route::get('/organismos', function (\Illuminate\Http\Request $request) {
    $query = Organismo::query();

    if ($search = $request->input('search')) {
        $query->where('nombre', 'like', "%{$search}%");
    }

    $organismos = cache()->remember('organismos_page_' . request('page', 1) . '_search_' . ($search ?? ''), 3600, function () use ($query) {
        return $query
            ->select('organismos.*')
            ->withCount('licitaciones')
            ->withSum('licitaciones as total_importe', 'importe_total')
            ->orderByDesc('total_importe')
            ->paginate(15);
    });

    // Cache stats for performance
    $totalOrganismos = cache()->remember('organismos_count', 3600, fn() => Organismo::count());
    $totalVolumen = cache()->remember('licitaciones_sum_total', 3600, fn() => Licitacion::sum('importe_total'));

    return view('organismos', compact('organismos', 'totalOrganismos', 'totalVolumen'));
})->name('organismos');

Route::get('/organismo/{id}', function ($id) {
    return view('organismo', [
        'organismo' => Organismo::find($id),
    ]);
})->name('organismo.show');

// Empresas
Route::get('/empresas', function (\Illuminate\Http\Request $request) {
    $query = \Illuminate\Support\Facades\DB::table('adjudicacions')
        ->join('empresas', 'adjudicacions.empresa_id', '=', 'empresas.id')
        ->select(
            'empresas.id',
            'empresas.nombre',
            'empresas.identificador',
            \Illuminate\Support\Facades\DB::raw('SUM(adjudicacions.importe) as total_importe'),
            \Illuminate\Support\Facades\DB::raw('COUNT(adjudicacions.id) as total_adjudicaciones')
        )
        ->groupBy('empresas.id', 'empresas.nombre', 'empresas.identificador');

    if ($search = $request->input('search')) {
        $query->where('empresas.nombre', 'like', "%{$search}%");
        $query->orWhere('empresas.identificador', 'like', "%{$search}%");
    }

    $empresas = $query->orderByDesc('total_importe')
        ->paginate(15)
        ->withQueryString();

    // Stats cache
    $totalVolumen = cache()->remember('adjudicaciones_sum_total', 3600, fn() => \App\Models\Adjudicacion::sum('importe'));
    $totalEmpresas = cache()->remember('empresas_count', 3600, fn() => Empresa::count());

    return view('empresas', compact('empresas', 'totalVolumen', 'totalEmpresas'));
})->name('empresas');

Route::get('/empresa/{id}', function ($id) {
    return view('empresa', [
        'empresa' => Empresa::find($id),
    ]);
})->name('empresa.show');