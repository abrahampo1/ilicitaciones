<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WrappedController extends Controller
{
    // Población de España (INE, 2025). Solo para la equivalencia €/habitante del wrapped.
    private const POBLACION_ESPANA = 48_800_000;

    public function index()
    {
        $years = $this->availableYears();

        if ($years->isEmpty()) {
            return view('wrapped.index', ['years' => collect(), 'totalesPorYear' => []]);
        }

        // Total por año para las tarjetas del selector; misma fuente que el wrapped.
        $totalesPorYear = cache()->remember('wrapped:index_totales:v2', 21600, function () use ($years) {
            $yearExpr = $this->yearExpr('fecha_adjudicacion');

            return DB::table('adjudicacions')
                ->selectRaw("{$yearExpr} as y, SUM(importe) as total")
                ->whereNotNull('fecha_adjudicacion')
                ->whereIn(DB::raw($yearExpr), $years->all())
                ->groupBy('y')
                ->pluck('total', 'y')
                ->all();
        });

        return view('wrapped.index', compact('years', 'totalesPorYear'));
    }

    public function show(int $year)
    {
        $years = $this->availableYears();

        abort_unless($years->contains($year), 404);

        $wrapped = $this->wrappedData($year);

        $prevYear = $years->filter(fn ($y) => $y < $year)->max();
        $nextYear = $years->filter(fn ($y) => $y > $year)->min();

        return view('wrapped.show', compact('wrapped', 'years', 'year', 'prevYear', 'nextYear'));
    }

    /**
     * Paquete del wrapped con doble nivel de persistencia: caché (rápida, pero la
     * borra el Cache::flush() de RecalcularEstadisticas) y tabla `estadisticas`
     * (sobrevive al flush, evita repetir el build pesado en un request tras cada
     * importación). Años cerrados se refrescan cada 7 días; el año en curso, cada 6h.
     */
    private function wrappedData(int $year): array
    {
        $maxAge = $year < (int) now()->format('Y') ? 604800 : 21600;

        return cache()->remember("wrapped:v2:{$year}", $maxAge, function () use ($year, $maxAge) {
            $row = DB::table('estadisticas')->where('clave', "wrapped_{$year}")->first();

            if ($row && $row->updated_at && Carbon::parse($row->updated_at)->gt(now()->subSeconds($maxAge))) {
                return json_decode($row->valor, true);
            }

            $wrapped = $this->build($year);

            DB::table('estadisticas')->updateOrInsert(
                ['clave' => "wrapped_{$year}"],
                ['valor' => json_encode($wrapped), 'updated_at' => now()]
            );

            return $wrapped;
        });
    }

    /**
     * Años con adjudicaciones, desde la tabla precalculada inversiones_anuales
     * (consulta directa con YEAR() sería un full scan). Se acota a [2000, año actual]
     * porque los datos importados traen a veces fechas corruptas. Fallback a la tabla
     * cruda solo en el cold start previo al primer RecalcularEstadisticas.
     */
    private function availableYears()
    {
        return cache()->remember('wrapped:years:v2', 21600, function () {
            $years = DB::table('inversiones_anuales')
                ->where('entity_type', 'empresa')
                ->whereBetween('year', [2000, (int) now()->format('Y')])
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($y) => (int) $y);

            if ($years->isNotEmpty()) {
                return $years;
            }

            $yearExpr = $this->yearExpr('fecha_adjudicacion');

            return DB::table('adjudicacions')
                ->selectRaw("DISTINCT {$yearExpr} as y")
                ->whereNotNull('fecha_adjudicacion')
                ->whereBetween(DB::raw($yearExpr), [2000, (int) now()->format('Y')])
                ->orderByDesc('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y);
        });
    }

    /**
     * Calcula el paquete completo de datos del wrapped de un año. Consultas pesadas,
     * pero solo se ejecutan al expirar la persistencia (patrón cold-start del sitio).
     */
    private function build(int $year): array
    {
        // Rango semiabierto: robusto aunque fecha_adjudicacion traiga hora (en SQLite
        // el BETWEEN textual con tope 'YYYY-12-31' excluiría el 31 de diciembre).
        $desde = "{$year}-01-01";
        $hastaExcl = ($year + 1).'-01-01';

        // Para el año en curso se compara contra el mismo periodo del año anterior y
        // el ritmo se calcula sobre los días transcurridos, no sobre el año entero.
        $enCurso = $year === (int) now()->format('Y');
        $diasAnio = Carbon::create($year)->isLeapYear() ? 366 : 365;
        $dias = $enCurso ? max(1, now()->dayOfYear) : $diasAnio;

        $prevDesde = ($year - 1).'-01-01';
        $prevHastaExcl = $enCurso ? now()->subYear()->addDay()->format('Y-m-d') : $year.'-01-01';

        $base = fn () => DB::table('adjudicacions')
            ->where('fecha_adjudicacion', '>=', $desde)
            ->where('fecha_adjudicacion', '<', $hastaExcl);

        $totales = $base()
            ->selectRaw('COALESCE(SUM(importe), 0) as total, COUNT(*) as num, COUNT(DISTINCT empresa_id) as empresas, COUNT(DISTINCT licitacion_id) as licitaciones')
            ->first();

        $prevTotal = (float) DB::table('adjudicacions')
            ->where('fecha_adjudicacion', '>=', $prevDesde)
            ->where('fecha_adjudicacion', '<', $prevHastaExcl)
            ->sum('importe');

        $monthExpr = $this->monthExpr('fecha_adjudicacion');
        $porMes = $base()
            ->selectRaw("{$monthExpr} as mes, SUM(importe) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->all();
        $porMes = array_replace(array_fill(1, 12, 0.0), array_map('floatval', $porMes));
        arsort($porMes);
        $mesTop = ['mes' => array_key_first($porMes), 'total' => reset($porMes)];
        ksort($porMes);

        $topOrganismos = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->join('organismos', 'organismos.id', '=', 'licitacions.organismo_id')
            ->selectRaw('organismos.id, organismos.nombre, SUM(adjudicacions.importe) as total, COUNT(*) as num')
            ->groupBy('organismos.id', 'organismos.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $numOrganismos = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->whereNotNull('licitacions.organismo_id')
            ->distinct()
            ->count('licitacions.organismo_id');

        $topEmpresas = $base()
            ->join('empresas', 'empresas.id', '=', 'adjudicacions.empresa_id')
            ->selectRaw('empresas.id, empresas.nombre, SUM(adjudicacions.importe) as total, COUNT(*) as num')
            ->groupBy('empresas.id', 'empresas.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topCategorias = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->join('categorias', 'categorias.id', '=', 'licitacions.categoria_id')
            ->selectRaw('categorias.id, categorias.nombre, SUM(adjudicacions.importe) as total, COUNT(*) as num')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $mayor = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->leftJoin('organismos', 'organismos.id', '=', 'licitacions.organismo_id')
            ->leftJoin('empresas', 'empresas.id', '=', 'adjudicacions.empresa_id')
            ->whereNotNull('adjudicacions.importe')
            ->orderByDesc('adjudicacions.importe')
            ->selectRaw('licitacions.id as licitacion_id, licitacions.titulo, adjudicacions.importe, organismos.nombre as organismo, empresas.nombre as empresa')
            ->first();

        // Códigos CODICE compartidos con los detectores del periódico de datos.
        $urgenciaCodigos = array_map('strval', (array) config('periodico.umbrales.urgencia_codigos', ['2', '3']));
        $sinCompetenciaCodigos = array_map('strval', (array) config('periodico.umbrales.sin_competencia_codigos', ['3', '6']));

        $urgentes = $base()
            ->whereIn('urgencia', $urgenciaCodigos)
            ->selectRaw('COUNT(*) as num, COALESCE(SUM(importe), 0) as importe')
            ->first();

        $sinCompetencia = $base()
            ->whereIn('tipo_procedimiento', $sinCompetenciaCodigos)
            ->selectRaw('COUNT(*) as num, COALESCE(SUM(importe), 0) as importe')
            ->first();

        $total = (float) $totales->total;
        $num = (int) $totales->num;

        // Todo arrays puros: el paquete se persiste como JSON en `estadisticas` y la
        // vista debe recibir la misma forma venga del build o de la rehidratación.
        return [
            'year' => $year,
            'enCurso' => $enCurso,
            'total' => $total,
            'numAdjudicaciones' => $num,
            'numLicitaciones' => (int) $totales->licitaciones,
            'numEmpresas' => (int) $totales->empresas,
            'numOrganismos' => $numOrganismos,
            'prevTotal' => $prevTotal,
            'deltaPct' => $prevTotal > 0 ? round(($total - $prevTotal) / $prevTotal * 100, 1) : null,
            'porMes' => $porMes,
            'mesTop' => $mesTop,
            'topOrganismos' => $topOrganismos->map(fn ($r) => (array) $r)->all(),
            'topEmpresas' => $topEmpresas->map(fn ($r) => (array) $r)->all(),
            'topCategorias' => $topCategorias->map(fn ($r) => (array) $r)->all(),
            'mayorAdjudicacion' => $mayor ? (array) $mayor : null,
            'urgentes' => [
                'num' => (int) $urgentes->num,
                'importe' => (float) $urgentes->importe,
                'pct' => $num > 0 ? round($urgentes->num / $num * 100, 1) : 0,
            ],
            'sinCompetencia' => [
                'num' => (int) $sinCompetencia->num,
                'importe' => (float) $sinCompetencia->importe,
                'pct' => $num > 0 ? round($sinCompetencia->num / $num * 100, 1) : 0,
            ],
            'porDia' => $total / $dias,
            'porSegundo' => $total / ($dias * 86400),
            'porHabitante' => $total / self::POBLACION_ESPANA,
        ];
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function yearExpr(string $column): string
    {
        return $this->isMysql()
            ? "YEAR({$column})"
            : "CAST(strftime('%Y', {$column}) AS INTEGER)";
    }

    private function monthExpr(string $column): string
    {
        return $this->isMysql()
            ? "MONTH({$column})"
            : "CAST(strftime('%m', {$column}) AS INTEGER)";
    }
}
