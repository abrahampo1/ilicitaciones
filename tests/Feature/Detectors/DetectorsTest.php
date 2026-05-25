<?php

use App\Analysis\Detectors\AdjudicatarioUnicoDetector;
use App\Analysis\Detectors\ConcentracionDetector;
use App\Analysis\Detectors\SobrecosteDetector;
use App\Analysis\Detectors\UrgenciaDetector;
use App\Jobs\RecalcularAgregadosDimension;
use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;

beforeEach(function () {
    // Ventanas amplias para que los datos históricos de los factories entren.
    config(['periodico.ventana_dias' => 1_000_000, 'periodico.ventana_meses' => 1_000_000]);
});

it('detecta adjudicatario único por encima del umbral', function () {
    $org = Organismo::factory()->create();

    $unico = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 2_000_000]);
    Adjudicacion::factory()->create(['licitacion_id' => $unico->id, 'empresa_id' => Empresa::factory(), 'importe' => 2_000_000]);

    $multiple = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 2_000_000]);
    Adjudicacion::factory()->count(2)->create(['licitacion_id' => $multiple->id, 'empresa_id' => Empresa::factory()]);

    $pequeno = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 500_000]);
    Adjudicacion::factory()->create(['licitacion_id' => $pequeno->id, 'empresa_id' => Empresa::factory(), 'importe' => 500_000]);

    $candidatos = iterator_to_array((new AdjudicatarioUnicoDetector)->detect());

    expect($candidatos)->toHaveCount(1)
        ->and($candidatos[0]->tipo)->toBe('adjudicatario_unico')
        ->and($candidatos[0]->payload['licitacion']['identificador'])->toBe($unico->identificador);
});

it('detecta sobrecoste sobre el presupuesto con floor de importe', function () {
    $org = Organismo::factory()->create();

    $sobre = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 1_000_000]);
    Adjudicacion::factory()->create(['licitacion_id' => $sobre->id, 'empresa_id' => Empresa::factory(), 'importe' => 1_300_000]);

    // Mismo % de desviación pero por debajo del floor (500k) => se ignora.
    $pequeno = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 100_000]);
    Adjudicacion::factory()->create(['licitacion_id' => $pequeno->id, 'empresa_id' => Empresa::factory(), 'importe' => 200_000]);

    $candidatos = iterator_to_array((new SobrecosteDetector)->detect());

    expect($candidatos)->toHaveCount(1)
        ->and($candidatos[0]->payload['desviacion_pct'])->toBe(30.0);
});

it('detecta concentración de mercado desde los agregados', function () {
    config([
        'periodico.umbrales.concentracion_volumen_min' => 100_000,
        'periodico.umbrales.concentracion_min_contratos' => 3,
    ]);

    $org = Organismo::factory()->create();
    $dominante = Empresa::factory()->create();
    $menor = Empresa::factory()->create();

    // 3 adjudicaciones de la dominante (720k) en el mismo año y organismo.
    foreach ([240_000, 240_000, 240_000] as $i => $imp) {
        $lic = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => $imp]);
        Adjudicacion::factory()->create([
            'licitacion_id' => $lic->id, 'empresa_id' => $dominante->id,
            'importe' => $imp, 'fecha_adjudicacion' => '2023-06-0'.($i + 1),
        ]);
    }
    $lic = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 280_000]);
    Adjudicacion::factory()->create([
        'licitacion_id' => $lic->id, 'empresa_id' => $menor->id,
        'importe' => 280_000, 'fecha_adjudicacion' => '2023-06-09',
    ]);

    (new RecalcularAgregadosDimension)->handle();

    $candidatos = iterator_to_array((new ConcentracionDetector)->detect());

    expect($candidatos)->toHaveCount(1)
        ->and($candidatos[0]->payload['share_pct'])->toBe(72.0)
        ->and((int) $candidatos[0]->payload['num_adjudicaciones'])->toBe(3);
});

it('detecta abuso de urgencia por organismo', function () {
    config([
        'periodico.umbrales.urgencia_min_total' => 4,
        'periodico.umbrales.urgencia_ratio' => 0.40,
        'periodico.umbrales.urgencia_codigos' => ['2', '3'],
    ]);

    $org = Organismo::factory()->create();
    $lic = Licitacion::factory()->create(['organismo_id' => $org->id]);

    // 5 adjudicaciones: 3 urgentes (cod 2/3) => ratio 0.6 >= 0.4
    foreach (['2', '3', '2', '1', '1'] as $cod) {
        Adjudicacion::factory()->create([
            'licitacion_id' => $lic->id, 'empresa_id' => Empresa::factory(),
            'urgencia' => $cod, 'importe' => 100_000, 'fecha_adjudicacion' => '2024-01-01',
        ]);
    }

    $candidatos = iterator_to_array((new UrgenciaDetector)->detect());

    expect($candidatos)->toHaveCount(1)
        ->and($candidatos[0]->payload['adjudicaciones_urgentes'])->toBe(3)
        ->and($candidatos[0]->payload['total_adjudicaciones'])->toBe(5);
});
