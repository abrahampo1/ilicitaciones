<?php

use App\Models\Adjudicacion;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config([
        'periodico.ventana_dias' => 1_000_000,
        'periodico.detectores_activos' => ['adjudicatario_unico'],
    ]);

    $org = Organismo::factory()->create();
    $lic = Licitacion::factory()->create(['organismo_id' => $org->id, 'importe_total' => 2_000_000]);
    Adjudicacion::factory()->create(['licitacion_id' => $lic->id, 'empresa_id' => Empresa::factory(), 'importe' => 2_000_000]);
});

it('persiste candidatos y es idempotente por signature', function () {
    $this->artisan('app:detectar-historias')->assertSuccessful();
    expect(DB::table('story_candidates')->count())->toBe(1);

    // Segunda pasada: no duplica.
    $this->artisan('app:detectar-historias')->assertSuccessful();
    expect(DB::table('story_candidates')->count())->toBe(1);
});

it('no reencola un candidato generado dentro del cooldown', function () {
    $this->artisan('app:detectar-historias');

    DB::table('story_candidates')->update([
        'estado' => 'generado',
        'article_id' => 99,
        'cooldown_until' => now()->addDays(30)->toDateTimeString(),
    ]);

    $this->artisan('app:detectar-historias');

    $row = DB::table('story_candidates')->first();
    expect($row->estado)->toBe('generado')
        ->and((int) $row->article_id)->toBe(99);
});

it('reencola un candidato generado tras expirar el cooldown', function () {
    $this->artisan('app:detectar-historias');

    DB::table('story_candidates')->update([
        'estado' => 'generado',
        'article_id' => 99,
        'cooldown_until' => now()->subDay()->toDateTimeString(),
    ]);

    $this->artisan('app:detectar-historias');

    $row = DB::table('story_candidates')->first();
    expect($row->estado)->toBe('pendiente')
        ->and($row->article_id)->toBeNull();
});
