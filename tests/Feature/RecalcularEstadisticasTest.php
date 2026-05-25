<?php

use App\Jobs\RecalcularEstadisticas;
use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function seedDatosBase(): array
{
    $org = Organismo::factory()->create();
    $empX = Empresa::factory()->create();
    $empY = Empresa::factory()->create();
    $empZ = Empresa::factory()->create(); // sin adjudicaciones

    $lic1 = Licitacion::factory()->create([
        'organismo_id' => $org->id,
        'importe_total' => 1000,
        'fecha_actualizacion' => '2020-01-15 00:00:00',
    ]);
    $lic2 = Licitacion::factory()->create([
        'organismo_id' => $org->id,
        'importe_total' => 2000,
        'fecha_actualizacion' => '2021-03-10 00:00:00',
    ]);

    Adjudicacion::factory()->create([
        'empresa_id' => $empX->id, 'licitacion_id' => $lic1->id,
        'importe' => 500, 'fecha_adjudicacion' => '2020-06-01',
    ]);
    Adjudicacion::factory()->create([
        'empresa_id' => $empX->id, 'licitacion_id' => $lic2->id,
        'importe' => 700, 'fecha_adjudicacion' => '2021-06-01',
    ]);
    Adjudicacion::factory()->create([
        'empresa_id' => $empY->id, 'licitacion_id' => $lic2->id,
        'importe' => 300, 'fecha_adjudicacion' => '2021-02-01',
    ]);

    return compact('org', 'empX', 'empY', 'empZ', 'lic1', 'lic2');
}

it('recalcula los totales de empresas en columnas precomputadas', function () {
    ['empX' => $empX, 'empY' => $empY, 'empZ' => $empZ] = seedDatosBase();

    (new RecalcularEstadisticas)->handle();

    expect((float) $empX->fresh()->total_importe)->toBe(1200.0)
        ->and($empX->fresh()->total_adjudicaciones)->toBe(2);
    expect((float) $empY->fresh()->total_importe)->toBe(300.0)
        ->and($empY->fresh()->total_adjudicaciones)->toBe(1);
    expect((float) $empZ->fresh()->total_importe)->toBe(0.0)
        ->and($empZ->fresh()->total_adjudicaciones)->toBe(0);
});

it('recalcula los totales de organismos', function () {
    ['org' => $org] = seedDatosBase();

    (new RecalcularEstadisticas)->handle();

    expect((float) $org->fresh()->total_importe)->toBe(3000.0)
        ->and($org->fresh()->total_licitaciones)->toBe(2);
});

it('construye la serie de inversiones anuales por entidad', function () {
    ['empX' => $empX, 'org' => $org] = seedDatosBase();

    (new RecalcularEstadisticas)->handle();

    $empAnual = DB::table('inversiones_anuales')
        ->where('entity_type', 'empresa')->where('entity_id', $empX->id)
        ->pluck('total', 'year');
    expect((float) $empAnual[2020])->toBe(500.0)
        ->and((float) $empAnual[2021])->toBe(700.0);

    $orgAnual = DB::table('inversiones_anuales')
        ->where('entity_type', 'organismo')->where('entity_id', $org->id)
        ->pluck('total', 'year');
    expect((float) $orgAnual[2020])->toBe(1000.0)
        ->and((float) $orgAnual[2021])->toBe(2000.0);
});

it('persiste las estadisticas globales del home', function () {
    seedDatosBase();

    (new RecalcularEstadisticas)->handle();

    $stats = json_decode(DB::table('estadisticas')->where('clave', 'home_stats')->value('valor'), true);

    expect($stats['conteoLicitaciones'])->toBe(2)
        ->and($stats['totalOrganismos'])->toBe(1)
        ->and($stats['totalEmpresas'])->toBe(3)
        ->and((float) $stats['totalImporte'])->toBe(3000.0)
        ->and((float) $stats['totalVolumenAdjudicado'])->toBe(1500.0);

    $tops = json_decode(DB::table('estadisticas')->where('clave', 'home_top_empresas')->value('valor'), true);
    expect($tops)->toHaveCount(2); // empZ (sin adj) queda fuera
});

it('invalida los caches de listados al recalcular', function () {
    seedDatosBase();

    // Simula una página de listado cacheada con datos antiguos (p.ej. en cero).
    Cache::put('empresas_abc123', 'stale', 3600);
    Cache::put('organismos_xyz789', 'stale', 3600);

    (new RecalcularEstadisticas)->handle();

    expect(Cache::has('empresas_abc123'))->toBeFalse()
        ->and(Cache::has('organismos_xyz789'))->toBeFalse();
});

it('es idempotente: dos ejecuciones dan el mismo resultado', function () {
    ['empX' => $empX] = seedDatosBase();

    (new RecalcularEstadisticas)->handle();
    (new RecalcularEstadisticas)->handle();

    expect((float) $empX->fresh()->total_importe)->toBe(1200.0)
        ->and($empX->fresh()->total_adjudicaciones)->toBe(2)
        ->and(DB::table('inversiones_anuales')->where('entity_id', $empX->id)->where('entity_type', 'empresa')->count())->toBe(2);
});
