<?php

use App\Jobs\RecalcularEstadisticas;
use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;

/** Crea datos y deja los agregados ya calculados (como en producción tras el job). */
function seedYRecalcula(): array
{
    $org = Organismo::factory()->create(['nombre' => 'Ayuntamiento Test']);

    $grande = Empresa::factory()->create(['nombre' => 'Empresa Grande SA']);
    $pequena = Empresa::factory()->create(['nombre' => 'Empresa Pequena SL']);
    $sinAdj = Empresa::factory()->create(['nombre' => 'Empresa Fantasma SL']);

    $lic1 = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 5000000]);
    $lic2 = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 1000]);

    Adjudicacion::factory()->create(['empresa_id' => $grande->id, 'licitacion_id' => $lic1->id, 'importe' => 5000000]);
    Adjudicacion::factory()->create(['empresa_id' => $pequena->id, 'licitacion_id' => $lic2->id, 'importe' => 1000]);

    (new RecalcularEstadisticas)->handle();

    return compact('org', 'grande', 'pequena', 'sinAdj', 'lic1', 'lic2');
}

it('home responde 200 y muestra estadisticas precomputadas', function () {
    seedYRecalcula();

    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Empresa Grande SA');
});

it('listado de empresas responde 200, ordena por importe y oculta las sin adjudicaciones', function () {
    $d = seedYRecalcula();

    $res = $this->get('/empresas')->assertStatus(200);

    $res->assertSee('Empresa Grande SA');
    $res->assertSee('Empresa Pequena SL');
    $res->assertDontSee('Empresa Fantasma SL'); // total_adjudicaciones = 0
    $res->assertSeeInOrder(['Empresa Grande SA', 'Empresa Pequena SL']); // mayor importe primero
});

it('filtro importe_min en empresas usa la columna precomputada', function () {
    seedYRecalcula();

    $this->get('/empresas?importe_min=1000000')
        ->assertStatus(200)
        ->assertSee('Empresa Grande SA')
        ->assertDontSee('Empresa Pequena SL');
});

it('busqueda por nombre en empresas', function () {
    seedYRecalcula();

    $this->get('/empresas?search=Grande')
        ->assertStatus(200)
        ->assertSee('Empresa Grande SA')
        ->assertDontSee('Empresa Pequena SL');
});

it('listado de organismos responde 200 con totales', function () {
    seedYRecalcula();

    $this->get('/organismos')
        ->assertStatus(200)
        ->assertSee('Ayuntamiento Test');
});

it('ficha de empresa responde 200', function () {
    $d = seedYRecalcula();

    $this->get('/empresa/'.$d['grande']->id)->assertStatus(200);
});

it('ficha de organismo responde 200', function () {
    $d = seedYRecalcula();

    $this->get('/organismo/'.$d['org']->id)->assertStatus(200);
});

it('ficha de licitacion responde 200', function () {
    $d = seedYRecalcula();

    $this->get('/licitacion/'.$d['lic1']->id)->assertStatus(200);
});

it('home no rompe cuando aun no hay estadisticas calculadas', function () {
    // Sin ejecutar el job: cold start debe servir 200 con valores a cero.
    $this->get('/')->assertStatus(200);
});
