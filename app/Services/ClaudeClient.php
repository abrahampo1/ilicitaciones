<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo de la API Messages de Anthropic (sin SDK oficial). Fuerza salida
 * estructurada vía tool-use y cachea el system prompt largo (prompt caching).
 */
class ClaudeClient
{
    /** @var array<string,mixed> */
    private array $cfg;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? (array) config('services.anthropic');
    }

    /**
     * @param  array<string,mixed>  $payload  datos exactos del candidato
     * @return array{title:string,dek:string,body:string,suggested_section:string,confidence:float}
     */
    public function redactar(string $systemPrompt, array $payload): array
    {
        if (empty($this->cfg['key'])) {
            throw new RuntimeException('ANTHROPIC_API_KEY no configurada.');
        }

        $resp = Http::withHeaders([
            'x-api-key' => $this->cfg['key'],
            'anthropic-version' => $this->cfg['version'] ?? '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout((int) ($this->cfg['timeout'] ?? 60))
            ->retry(3, 2000, function ($e) {
                if ($e instanceof ConnectionException) {
                    return true;
                }

                return $e instanceof RequestException
                    && in_array($e->response?->status(), [429, 500, 502, 503, 529], true);
            }, throw: false)
            ->post(rtrim($this->cfg['base_url'] ?? 'https://api.anthropic.com', '/').'/v1/messages', [
                'model' => $this->cfg['model'] ?? 'claude-sonnet-4-6',
                'max_tokens' => (int) ($this->cfg['max_tokens'] ?? 2000),
                'system' => [[
                    'type' => 'text',
                    'text' => $systemPrompt,
                    'cache_control' => ['type' => 'ephemeral'], // prompt caching del estilo editorial
                ]],
                'tools' => [$this->articleTool()],
                'tool_choice' => ['type' => 'tool', 'name' => 'emitir_articulo'],
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->userBlock($payload),
                ]],
            ]);

        $resp->throw();

        $bloque = collect($resp->json('content', []))->firstWhere('type', 'tool_use');

        if (! $bloque || ! isset($bloque['input'])) {
            throw new RuntimeException('Respuesta de Claude sin bloque tool_use.');
        }

        return $bloque['input'];
    }

    /** @return array<string,mixed> */
    private function articleTool(): array
    {
        return [
            'name' => 'emitir_articulo',
            'description' => 'Devuelve el borrador del artículo usando EXCLUSIVAMENTE las cifras del payload.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['title', 'dek', 'body', 'suggested_section', 'confidence'],
                'properties' => [
                    'title' => ['type' => 'string', 'maxLength' => 140],
                    'dek' => ['type' => 'string', 'maxLength' => 300],
                    'body' => ['type' => 'string', 'description' => 'Markdown en español, sobrio'],
                    'suggested_section' => ['type' => 'string', 'enum' => ['rankings', 'alertas', 'informes', 'perfiles']],
                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                ],
            ],
        ];
    }

    private function userBlock(array $payload): string
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "<datos_oficiales>\n{$json}\n</datos_oficiales>\n\n"
            .'Redacta el borrador usando EXCLUSIVAMENTE estos datos. No inventes cifras, '
            .'nombres ni hechos que no aparezcan arriba.';
    }
}
