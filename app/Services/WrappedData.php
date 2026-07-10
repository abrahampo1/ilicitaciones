<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos del Wrapped anual. Doble nivel de lectura para que las páginas carguen
 * rápido: caché (la borra el Cache::flush() de RecalcularEstadisticas) y tabla
 * `estadisticas` (la reescribe warm() en cada importación). En condiciones
 * normales ningún request ejecuta las agregaciones: solo el cold start absoluto
 * de una instalación donde el job aún no ha corrido.
 */
class WrappedData
{
    /**
     * Versión del contrato del paquete. Al añadir campos nuevos hay que subirla:
     * paquete() descarta los paquetes persistidos con versión anterior y los
     * reconstruye, evitando índices indefinidos en la vista tras un despliegue.
     */
    private const SCHEMA_VERSION = 2;

    // Población de España (INE, 2025). Solo para la equivalencia €/habitante.
    private const POBLACION_ESPANA = 48_800_000;

    // Constantes de las equivalencias del slide "¿Cuánto es eso?" (valores medios
    // aproximados en España: salario bruto anual INE, vivienda tipo, km de autovía).
    private const SUELDO_MEDIO_ANUAL = 28_050;

    private const VIVIENDA_MEDIA = 200_000;

    private const KM_AUTOVIA = 5_000_000;

    /**
     * Precalcula y persiste todo lo que consume el Wrapped. Lo invoca
     * RecalcularEstadisticas tras cada importación.
     */
    public function warm(): void
    {
        $years = $this->queryYears();

        // Los paquetes primero y la lista de años al final: si un request llega a
        // mitad del warm, nunca ve listado un año cuyo paquete aún no existe (eso
        // dispararía el build pesado in-request que esta clase promete evitar).
        foreach ($years as $year) {
            $this->guardar("wrapped_{$year}", $this->build($year));
        }

        $this->guardar('wrapped_index_totales', $this->queryIndexTotales($years));
        $this->guardar('wrapped_years', $years->all());
    }

    /** Años con datos, de más reciente a más antiguo. */
    public function years(): Collection
    {
        return cache()->remember('wrapped:years:v3', 21600, function () {
            $fila = $this->leer('wrapped_years');

            if (is_array($fila)) {
                return collect($fila)->map(fn ($y) => (int) $y);
            }

            $years = $this->queryYears();
            $this->guardar('wrapped_years', $years->all());

            return $years;
        });
    }

    /** Total adjudicado por año para las tarjetas del índice. */
    public function indexTotales(): array
    {
        return cache()->remember('wrapped:index_totales:v3', 21600, function () {
            $fila = $this->leer('wrapped_index_totales');

            if (is_array($fila)) {
                return $fila;
            }

            $totales = $this->queryIndexTotales($this->years());
            $this->guardar('wrapped_index_totales', $totales);

            return $totales;
        });
    }

    /** Paquete completo del wrapped de un año. */
    public function paquete(int $year): array
    {
        return cache()->remember("wrapped:v4:{$year}", 21600, function () use ($year) {
            $fila = $this->leer("wrapped_{$year}");

            if (is_array($fila) && ($fila['v'] ?? 0) === self::SCHEMA_VERSION) {
                return $fila;
            }

            $wrapped = $this->build($year);
            $this->guardar("wrapped_{$year}", $wrapped);

            return $wrapped;
        });
    }

    /**
     * Años con adjudicaciones, desde la tabla precalculada inversiones_anuales
     * (consulta directa con YEAR() sería un full scan). Se acota a [2000, año actual]
     * porque los datos importados traen a veces fechas corruptas. Fallback a la tabla
     * cruda solo si inversiones_anuales aún no se ha calculado nunca.
     */
    private function queryYears(): Collection
    {
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
    }

    private function queryIndexTotales(Collection $years): array
    {
        $totales = [];
        foreach ($years as $year) {
            $totales[$year] = (float) DB::table('adjudicacions')
                ->where('fecha_adjudicacion', '>=', "{$year}-01-01")
                ->where('fecha_adjudicacion', '<', ($year + 1).'-01-01')
                ->sum('importe');
        }

        return $totales;
    }

    /**
     * Calcula el paquete completo de datos del wrapped de un año. Es la parte
     * pesada: solo corre dentro del job o en el cold start absoluto.
     */
    public function build(int $year): array
    {
        // Rango semiabierto: robusto aunque fecha_adjudicacion traiga hora (en SQLite
        // el BETWEEN textual con tope 'YYYY-12-31' excluiría el 31 de diciembre).
        $desde = "{$year}-01-01";
        $hastaExcl = ($year + 1).'-01-01';

        // Para el año en curso se compara contra el mismo periodo del año anterior y
        // el ritmo se calcula sobre los días transcurridos, no sobre el año entero.
        $enCurso = $year === (int) now()->format('Y');
        $diasAnio = now()->setDate($year, 1, 1)->isLeapYear() ? 366 : 365;
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

        // Top provincias por gasto (vía la provincia del organismo contratante).
        $topProvincias = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->join('organismos', 'organismos.id', '=', 'licitacions.organismo_id')
            ->whereNotNull('organismos.provincia')
            ->where('organismos.provincia', '!=', '')
            ->selectRaw('organismos.provincia, SUM(adjudicacions.importe) as total, COUNT(*) as num')
            ->groupBy('organismos.provincia')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // La pareja organismo→empresa que más dinero movió entre sí.
        $duo = $base()
            ->join('licitacions', 'licitacions.id', '=', 'adjudicacions.licitacion_id')
            ->join('organismos', 'organismos.id', '=', 'licitacions.organismo_id')
            ->join('empresas', 'empresas.id', '=', 'adjudicacions.empresa_id')
            ->selectRaw('organismos.nombre as organismo, empresas.nombre as empresa, SUM(adjudicacions.importe) as total, COUNT(*) as num')
            ->groupBy('licitacions.organismo_id', 'adjudicacions.empresa_id', 'organismos.nombre', 'empresas.nombre')
            ->orderByDesc('total')
            ->limit(1)
            ->first();

        // Concentración: cuota del top 10 de empresas sobre el total del año.
        $top10Total = (float) DB::query()->fromSub(
            $base()
                ->whereNotNull('empresa_id')
                ->selectRaw('SUM(importe) as t')
                ->groupBy('empresa_id')
                ->orderByDesc('t')
                ->limit(10),
            'top10'
        )->sum('t');

        // El día con más dinero adjudicado. date() trunca la hora y es válido tanto
        // en MySQL (DATE()) como en SQLite (date()).
        $diaRecord = $base()
            ->selectRaw('date(fecha_adjudicacion) as fecha, SUM(importe) as total, COUNT(*) as num')
            ->groupBy(DB::raw('date(fecha_adjudicacion)'))
            ->orderByDesc('total')
            ->limit(1)
            ->first();

        // Día de la semana con más firmas (0 = domingo … 6 = sábado en ambos motores).
        $dowExpr = $this->isMysql()
            ? '(DAYOFWEEK(fecha_adjudicacion) - 1)'
            : "CAST(strftime('%w', fecha_adjudicacion) AS INTEGER)";
        $diaSemanaTop = $base()
            ->selectRaw("{$dowExpr} as dow, COUNT(*) as num")
            ->groupBy(DB::raw($dowExpr))
            ->orderByDesc('num')
            ->limit(1)
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
            'v' => self::SCHEMA_VERSION,
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
            'topProvincias' => $topProvincias->map(fn ($r) => (array) $r)->all(),
            'mayorAdjudicacion' => $mayor ? (array) $mayor : null,
            'duo' => $duo ? (array) $duo : null,
            'concentracion' => [
                'pctTop10' => $total > 0 ? round($top10Total / $total * 100, 1) : 0,
            ],
            'diaRecord' => $diaRecord ? (array) $diaRecord : null,
            'diaSemanaTop' => $diaSemanaTop ? (int) $diaSemanaTop->dow : null,
            'equivalencias' => [
                'sueldos' => (int) floor($total / self::SUELDO_MEDIO_ANUAL),
                'viviendas' => (int) floor($total / self::VIVIENDA_MEDIA),
                'kmAutovia' => (int) floor($total / self::KM_AUTOVIA),
            ],
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

    private function leer(string $clave): ?array
    {
        $valor = DB::table('estadisticas')->where('clave', $clave)->value('valor');

        return $valor !== null ? json_decode($valor, true) : null;
    }

    private function guardar(string $clave, array $valor): void
    {
        // upsert atómico (ON DUPLICATE KEY / ON CONFLICT): guardar() también corre
        // en requests (fallback cold-start) y updateOrInsert pierde la carrera.
        DB::table('estadisticas')->upsert(
            [['clave' => $clave, 'valor' => json_encode($valor), 'updated_at' => now()]],
            ['clave'],
            ['valor', 'updated_at']
        );
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
