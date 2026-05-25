<?php

use App\Jobs\GenerarBorradorArticulo;
use App\Models\Article;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'services.anthropic' => ['key' => 'test', 'base_url' => 'https://api.anthropic.com', 'version' => '2023-06-01', 'model' => 'claude-sonnet-4-6', 'max_tokens' => 2000, 'timeout' => 60],
        'periodico.generacion.confidence_min' => 0.5,
        'periodico.cooldown_dias.alertas' => 90,
    ]);
});

function candidato(array $over = []): int
{
    return DB::table('story_candidates')->insertGetId(array_merge([
        'tipo' => 'concentracion',
        'seccion' => 'alertas',
        'signature' => 'sig-'.uniqid(),
        'score' => 80,
        'payload' => json_encode(['importe_empresa' => 720000, 'share_pct' => 72]),
        'entidades' => json_encode([]),
        'estado' => 'generando',
        'created_at' => now(),
        'updated_at' => now(),
    ], $over));
}

function fakeRedaccion(string $body, float $confidence): void
{
    Http::fake(['*' => Http::response(['content' => [[
        'type' => 'tool_use', 'name' => 'emitir_articulo',
        'input' => ['title' => 'Titular', 'dek' => 'Dek', 'body' => $body, 'suggested_section' => 'alertas', 'confidence' => $confidence],
    ]]])]);
}

it('crea un borrador y marca el candidato como generado', function () {
    $empresa = Empresa::factory()->create();
    $id = candidato(['entidades' => json_encode([['type' => 'empresa', 'id' => $empresa->id, 'primary' => true]])]);

    fakeRedaccion('La empresa concentró 720.000 € (72%).', 0.9);

    GenerarBorradorArticulo::dispatchSync($id);

    $article = Article::first();
    expect($article)->not->toBeNull()
        ->and($article->status->value)->toBe('draft')
        ->and($article->empresas()->count())->toBe(1);

    $cand = DB::table('story_candidates')->find($id);
    expect($cand->estado)->toBe('generado')
        ->and((int) $cand->article_id)->toBe($article->id)
        ->and($cand->cooldown_until)->not->toBeNull();
});

it('usa OpenAI (gpt-5) cuando se indica ese proveedor', function () {
    config(['services.openai' => ['key' => 'sk-test', 'base_url' => 'https://api.openai.com', 'model' => 'gpt-5', 'max_tokens' => 2000, 'timeout' => 60]]);
    $id = candidato();

    Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'title' => 'Titular GPT', 'dek' => 'Dek', 'body' => 'La empresa concentró 720.000 € (72%).',
        'suggested_section' => 'alertas', 'confidence' => 0.9,
    ])]]]])]);

    GenerarBorradorArticulo::dispatchSync($id, 'openai');

    expect(Article::where('title', 'Titular GPT')->exists())->toBeTrue();
    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.openai.com'));
});

it('NO crea artículo si el FactChecker detecta una cifra inventada', function () {
    $id = candidato();

    fakeRedaccion('La empresa facturó 9.999.999 € no presentes en los datos.', 0.95);

    GenerarBorradorArticulo::dispatchSync($id);

    expect(Article::count())->toBe(0);
    $cand = DB::table('story_candidates')->find($id);
    expect($cand->estado)->toBe('error')
        ->and($cand->last_error)->toContain('Cifra no verificada');
});

it('deja el candidato pendiente si la confianza es baja', function () {
    $id = candidato();

    fakeRedaccion('La empresa concentró 720.000 € (72%).', 0.3);

    GenerarBorradorArticulo::dispatchSync($id);

    expect(Article::count())->toBe(0);
    expect(DB::table('story_candidates')->find($id)->estado)->toBe('pendiente');
});

it('el comando encola solo candidatos por encima del min-score y respeta el cupo', function () {
    Queue::fake();

    candidato(['estado' => 'pendiente', 'score' => 80, 'signature' => 'a']);
    candidato(['estado' => 'pendiente', 'score' => 50, 'signature' => 'b']);
    candidato(['estado' => 'pendiente', 'score' => 10, 'signature' => 'c']); // bajo umbral

    $this->artisan('app:generar-borradores', ['--min-score' => 40])->assertSuccessful();

    Queue::assertPushed(GenerarBorradorArticulo::class, 2);
    expect(DB::table('story_candidates')->where('estado', 'generando')->count())->toBe(2);
});

it('dry-run no encola nada', function () {
    Queue::fake();
    candidato(['estado' => 'pendiente', 'score' => 80, 'signature' => 'x']);

    $this->artisan('app:generar-borradores', ['--dry-run' => true])->assertSuccessful();

    Queue::assertNothingPushed();
    expect(DB::table('story_candidates')->where('estado', 'pendiente')->count())->toBe(1);
});
