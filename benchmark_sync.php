<?php
/**
 * Benchmark: contracts:sync desde 2024 hasta 2026-03
 * Ejecutar: php benchmark_sync.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Contratos\Models\Licitacion;
use Modules\Contratos\Models\Organismo;
use Modules\Contratos\Models\Empresa;
use Modules\Contratos\Models\Adjudicacion;

// --- Snapshot ANTES ---
$before = [
    'licitaciones' => Licitacion::count(),
    'organismos' => Organismo::count(),
    'empresas' => Empresa::count(),
    'adjudicaciones' => Adjudicacion::count(),
];

echo "=== BENCHMARK: contracts:sync 2024-01 → 2026-03 ===" . PHP_EOL;
echo "Inicio: " . now()->format('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;
echo "--- Estado ANTES ---" . PHP_EOL;
foreach ($before as $k => $v) {
    echo "  {$k}: " . number_format($v) . PHP_EOL;
}
echo PHP_EOL;

// --- Generar meses ---
$months = collect();
$start = now()->setDate(2024, 1, 1)->startOfMonth();
$end = now()->setDate(2026, 3, 1)->startOfMonth();
while ($start <= $end) {
    $months->push($start->format('Ym'));
    $start->addMonth();
}

echo "Meses a procesar: {$months->count()}" . PHP_EOL;
echo PHP_EOL;

$globalStart = microtime(true);
$monthTimings = [];
$monthStats = [];

foreach ($months as $i => $month) {
    $monthStart = microtime(true);
    $licitBefore = Licitacion::count();

    echo "[" . ($i + 1) . "/{$months->count()}] Procesando {$month}..." . PHP_EOL;

    // Ejecutar el sync para este mes de forma síncrona
    $exitCode = Artisan::call('contracts:sync', [
        '--month' => $month,
        '--sync' => true,
    ]);

    $monthEnd = microtime(true);
    $elapsed = round($monthEnd - $monthStart, 2);
    $licitAfter = Licitacion::count();
    $newLicit = $licitAfter - $licitBefore;

    $monthTimings[$month] = $elapsed;
    $monthStats[$month] = $newLicit;

    $rate = $elapsed > 0 ? round($newLicit / $elapsed, 0) : 0;
    echo "  → {$elapsed}s | +{$newLicit} licitaciones | {$rate} licit/s" . PHP_EOL;

    // Liberar memoria
    gc_collect_cycles();
}

$globalEnd = microtime(true);
$totalTime = round($globalEnd - $globalStart, 2);

// --- Snapshot DESPUÉS ---
$after = [
    'licitaciones' => Licitacion::count(),
    'organismos' => Organismo::count(),
    'empresas' => Empresa::count(),
    'adjudicaciones' => Adjudicacion::count(),
];

echo PHP_EOL;
echo "=====================================" . PHP_EOL;
echo "=== REPORTE DE RENDIMIENTO ===" . PHP_EOL;
echo "=====================================" . PHP_EOL;
echo PHP_EOL;

echo "--- Tiempo total: {$totalTime}s (" . round($totalTime / 60, 1) . " min) ---" . PHP_EOL;
echo PHP_EOL;

echo "--- Estado DESPUÉS ---" . PHP_EOL;
foreach ($after as $k => $v) {
    $diff = $v - $before[$k];
    $sign = $diff >= 0 ? '+' : '';
    echo "  {$k}: " . number_format($v) . " ({$sign}" . number_format($diff) . ")" . PHP_EOL;
}
echo PHP_EOL;

echo "--- Desglose por mes ---" . PHP_EOL;
echo str_pad("Mes", 8) . str_pad("Tiempo", 12) . str_pad("Nuevas", 12) . "Licit/s" . PHP_EOL;
echo str_repeat("-", 44) . PHP_EOL;

$totalNew = 0;
foreach ($monthTimings as $month => $time) {
    $new = $monthStats[$month];
    $totalNew += $new;
    $rate = $time > 0 ? round($new / $time, 0) : 0;
    echo str_pad($month, 8)
        . str_pad("{$time}s", 12)
        . str_pad(number_format($new), 12)
        . $rate . PHP_EOL;
}

echo str_repeat("-", 44) . PHP_EOL;
echo str_pad("TOTAL", 8)
    . str_pad("{$totalTime}s", 12)
    . str_pad(number_format($totalNew), 12)
    . ($totalTime > 0 ? round($totalNew / $totalTime, 0) : 0) . PHP_EOL;

echo PHP_EOL;

// Estadísticas adicionales
$avgTime = count($monthTimings) > 0 ? round(array_sum($monthTimings) / count($monthTimings), 2) : 0;
$maxMonth = array_search(max($monthTimings), $monthTimings);
$minMonth = array_search(min($monthTimings), $monthTimings);

echo "--- Estadísticas ---" . PHP_EOL;
echo "  Tiempo medio/mes: {$avgTime}s" . PHP_EOL;
echo "  Mes más lento: {$maxMonth} (" . $monthTimings[$maxMonth] . "s)" . PHP_EOL;
echo "  Mes más rápido: {$minMonth} (" . $monthTimings[$minMonth] . "s)" . PHP_EOL;
echo "  Throughput global: " . ($totalTime > 0 ? round($totalNew / $totalTime, 0) : 0) . " licit/s" . PHP_EOL;
echo "  Memoria pico: " . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . " MB" . PHP_EOL;
echo PHP_EOL;

// Guardar reporte a fichero
$reportPath = storage_path('app/benchmark_report_' . now()->format('Ymd_His') . '.txt');
ob_start();
echo "BENCHMARK REPORT - " . now()->format('Y-m-d H:i:s') . PHP_EOL;
echo "Total: {$totalTime}s | +{$totalNew} licitaciones | Peak mem: " . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . " MB" . PHP_EOL;
$report = ob_get_clean();
echo "Reporte guardado en: {$reportPath}" . PHP_EOL;
