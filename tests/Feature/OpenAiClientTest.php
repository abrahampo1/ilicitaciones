<?php

use App\Services\OpenAiClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.openai' => [
        'key' => 'sk-test',
        'base_url' => 'https://api.openai.com',
        'model' => 'gpt-5',
        'max_tokens' => 2000,
        'timeout' => 60,
    ]]);
});

function fakeOpenAi(array $input): array
{
    return ['choices' => [['message' => ['content' => json_encode($input)]]]];
}

it('parsea la respuesta y envía modelo gpt-5 con structured output', function () {
    Http::fake(['*' => Http::response(fakeOpenAi([
        'title' => 'Titular', 'dek' => 'Entradilla', 'body' => 'Cuerpo',
        'suggested_section' => 'alertas', 'confidence' => 0.8,
    ]))]);

    $res = (new OpenAiClient)->redactar('SYSTEM', ['share_pct' => 72]);

    expect($res['title'])->toBe('Titular')
        ->and($res['suggested_section'])->toBe('alertas');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer sk-test')
            && $body['model'] === 'gpt-5'
            && $body['response_format']['type'] === 'json_schema'
            && ($body['response_format']['json_schema']['strict'] ?? false) === true;
    });
});

it('da un mensaje claro ante un 401 de OpenAI', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'invalid key']], 401)]);

    expect(fn () => (new OpenAiClient)->redactar('SYSTEM', []))
        ->toThrow(RuntimeException::class, 'OPENAI_API_KEY');
});

it('falla si no hay API key', function () {
    config(['services.openai.key' => null]);

    expect(fn () => (new OpenAiClient)->redactar('SYSTEM', []))
        ->toThrow(RuntimeException::class);
});
