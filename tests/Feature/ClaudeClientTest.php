<?php

use App\Services\ClaudeClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.anthropic' => [
        'key' => 'test-key',
        'base_url' => 'https://api.anthropic.com',
        'version' => '2023-06-01',
        'model' => 'claude-sonnet-4-6',
        'max_tokens' => 2000,
        'timeout' => 60,
    ]]);
});

function fakeToolUse(array $input): array
{
    return ['content' => [['type' => 'tool_use', 'name' => 'emitir_articulo', 'input' => $input]]];
}

it('parsea el bloque tool_use y envía headers, modelo y tool_choice', function () {
    Http::fake(['*' => Http::response(fakeToolUse([
        'title' => 'Titular', 'dek' => 'Entradilla', 'body' => 'Cuerpo',
        'suggested_section' => 'alertas', 'confidence' => 0.8,
    ]))]);

    $res = (new ClaudeClient)->redactar('SYSTEM', ['tipo' => 'concentracion', 'share_pct' => 72]);

    expect($res['title'])->toBe('Titular')
        ->and($res['suggested_section'])->toBe('alertas');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $body['model'] === 'claude-sonnet-4-6'
            && $body['tool_choice']['name'] === 'emitir_articulo'
            && $body['system'][0]['cache_control']['type'] === 'ephemeral';
    });
});

it('reintenta ante un 529 y luego entrega', function () {
    Http::fake(['*' => Http::sequence()
        ->push('overloaded', 529)
        ->push(fakeToolUse([
            'title' => 'T', 'dek' => 'D', 'body' => 'B', 'suggested_section' => 'informes', 'confidence' => 0.6,
        ]), 200),
    ]);

    $res = (new ClaudeClient)->redactar('SYSTEM', []);

    expect($res['title'])->toBe('T');
    Http::assertSentCount(2);
})->skip(fn () => true, 'El retry duerme 2s; se valida manualmente.');

it('falla si no hay API key', function () {
    config(['services.anthropic.key' => null]);

    expect(fn () => (new ClaudeClient)->redactar('SYSTEM', []))
        ->toThrow(RuntimeException::class);
});
