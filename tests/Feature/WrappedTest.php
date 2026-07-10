<?php

use App\Jobs\RecalcularEstadisticas;
use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Crea adjudicaciones en un año concreto y deja los agregados calculados. */
function seedWrapped(int $year): array
{
    $org = Organismo::factory()->create(['nombre' => 'Ministerio Wrapped Test']);
    $empresa = Empresa::factory()->create(['nombre' => 'Constructora Wrapped SA']);

    $lic = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 2000000]);
    Adjudicacion::factory()->create([
        'empresa_id' => $empresa->id,
        'licitacion_id' => $lic->id,
        'importe' => 2000000,
        'fecha_adjudicacion' => "{$year}-06-15",
    ]);

    (new RecalcularEstadisticas)->handle();

    return compact('org', 'empresa', 'lic');
}

it('el indice del wrapped responde 200 y lista los años con datos', function () {
    seedWrapped(2023);

    $this->get('/wrapped')
        ->assertStatus(200)
        ->assertSee('Wrapped')
        ->assertSee('2023');
});

it('el indice del wrapped no rompe sin datos', function () {
    $this->get('/wrapped')->assertStatus(200);
});

it('el wrapped de un año con datos muestra totales, organismos y empresas', function () {
    seedWrapped(2023);

    $this->get('/wrapped/2023')
        ->assertStatus(200)
        ->assertSee('Ministerio Wrapped Test')
        ->assertSee('Constructora Wrapped SA')
        ->assertSee('2 millones €');
});

it('un año sin datos devuelve 404', function () {
    seedWrapped(2023);

    $this->get('/wrapped/1999')->assertStatus(404);
    $this->get('/wrapped/2010')->assertStatus(404);
});

it('un año con cero inicial o no numerico devuelve 404', function () {
    seedWrapped(2023);

    $this->get('/wrapped/02023')->assertStatus(404);
    $this->get('/wrapped/abcd')->assertStatus(404);
});

it('incluye adjudicaciones del 31 de diciembre aunque la fecha traiga hora', function () {
    // Regresión: el importador guarda 'Y-m-d H:i:s' y en SQLite un BETWEEN con tope
    // 'YYYY-12-31' textual excluiría el último día del año.
    $d = seedWrapped(2023);

    Adjudicacion::factory()->create([
        'empresa_id' => $d['empresa']->id,
        'licitacion_id' => Licitacion::factory()->create(['organismo_id' => $d['org']->id])->id,
        'importe' => 3000000,
        'fecha_adjudicacion' => '2023-12-31 00:00:00',
    ]);
    (new RecalcularEstadisticas)->handle();

    // 2M (junio) + 3M (31 dic) = 5 millones.
    $this->get('/wrapped/2023')
        ->assertStatus(200)
        ->assertSee('5 millones €');
});

it('el año en curso compara contra el mismo periodo y lo dice en el texto', function () {
    $year = (int) now()->format('Y');
    seedWrapped($year - 1);
    seedWrapped($year);

    $this->get("/wrapped/{$year}")
        ->assertStatus(200)
        ->assertSee('lo que va de')
        ->assertSee('mismo periodo de '.($year - 1));
});

it('sobrevive al Cache::flush del job releyendo de la tabla estadisticas', function () {
    seedWrapped(2023);

    $primera = $this->get('/wrapped/2023')->assertStatus(200);

    expect(DB::table('estadisticas')->where('clave', 'wrapped_2023')->exists())->toBeTrue();

    Cache::flush();

    $segunda = $this->get('/wrapped/2023')->assertStatus(200);

    expect($segunda->getContent())->toBe($primera->getContent());
});
