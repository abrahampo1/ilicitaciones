<?php

use App\Jobs\RecalcularAgregadosDimension;
use App\Models\Adjudicacion;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Facades\DB;

function seedDimension(): array
{
    $org = Organismo::factory()->create(['provincia' => 'Madrid']);
    $cat = Categoria::factory()->create();
    $empA = Empresa::factory()->create();
    $empB = Empresa::factory()->create();

    $lic = Licitacion::factory()->create([
        'organismo_id' => $org->id,
        'categoria_id' => $cat->id,
        'importe_total' => 1000,
        'fecha_actualizacion' => '2021-05-01 00:00:00',
    ]);

    Adjudicacion::factory()->create([
        'empresa_id' => $empA->id, 'licitacion_id' => $lic->id,
        'importe' => 600, 'fecha_adjudicacion' => '2021-06-01',
    ]);
    Adjudicacion::factory()->create([
        'empresa_id' => $empB->id, 'licitacion_id' => $lic->id,
        'importe' => 400, 'fecha_adjudicacion' => '2021-06-01',
    ]);

    return compact('org', 'cat', 'empA', 'empB', 'lic');
}

it('agrega por CPV con importe y empresas distintas', function () {
    ['cat' => $cat] = seedDimension();

    (new RecalcularAgregadosDimension)->handle();

    $row = DB::table('agregados_dimension')
        ->where('dimension', 'cpv')->where('key_a', $cat->id)->where('year', 2021)->first();

    expect((float) $row->total_importe)->toBe(1000.0)
        ->and((int) $row->num_adjudicaciones)->toBe(2)
        ->and((int) $row->num_empresas)->toBe(2);
});

it('agrega por provincia con volumen de licitaciones', function () {
    seedDimension();

    (new RecalcularAgregadosDimension)->handle();

    $row = DB::table('agregados_dimension')
        ->where('dimension', 'provincia')->where('key_a', 'Madrid')->where('year', 2021)->first();

    expect((float) $row->total_importe)->toBe(1000.0)
        ->and((int) $row->num_licitaciones)->toBe(1);
});

it('agrega pares empresa-organismo', function () {
    ['org' => $org, 'empA' => $empA, 'empB' => $empB] = seedDimension();

    (new RecalcularAgregadosDimension)->handle();

    $a = DB::table('agregados_dimension')->where('dimension', 'empresa_organismo')
        ->where('key_a', $empA->id)->where('key_b', $org->id)->where('year', 2021)->first();
    $b = DB::table('agregados_dimension')->where('dimension', 'empresa_organismo')
        ->where('key_a', $empB->id)->where('key_b', $org->id)->where('year', 2021)->first();

    expect((float) $a->total_importe)->toBe(600.0)
        ->and((float) $b->total_importe)->toBe(400.0);
});

it('es idempotente: dos ejecuciones no duplican filas', function () {
    seedDimension();

    (new RecalcularAgregadosDimension)->handle();
    (new RecalcularAgregadosDimension)->handle();

    expect(DB::table('agregados_dimension')->where('dimension', 'empresa_organismo')->count())->toBe(2);
});
