<?php

namespace App\Services\LLM;

use App\Services\ClaudeClient;
use App\Services\OpenAiClient;
use InvalidArgumentException;

/**
 * Resuelve el redactor LLM según el proveedor configurado (o un override explícito).
 */
class DrafterFactory
{
    public function make(?string $provider = null): ArticleDrafter
    {
        $provider ??= config('periodico.llm_provider', 'anthropic');

        return match ($provider) {
            'anthropic', 'claude' => app(ClaudeClient::class),
            'openai', 'gpt' => app(OpenAiClient::class),
            default => throw new InvalidArgumentException("Proveedor LLM desconocido: {$provider}"),
        };
    }
}
