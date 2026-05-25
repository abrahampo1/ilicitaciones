<?php

namespace App\Services;

use App\Services\LLM\ArticleDrafter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de la API de OpenAI (Chat Completions) para GPT-5. Fuerza salida estructurada
 * con Structured Outputs (response_format json_schema strict). Mismo contrato que Claude.
 */
class OpenAiClient implements ArticleDrafter
{
    /** @var array<string,mixed> */
    private array $cfg;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? (array) config('services.openai');
    }

    public function redactar(string $systemPrompt, array $payload): array
    {
        if (empty($this->cfg['key'])) {
            throw new RuntimeException('OPENAI_API_KEY no configurada.');
        }

        $resp = Http::withToken($this->cfg['key'])
            ->timeout((int) ($this->cfg['timeout'] ?? 60))
            ->retry(3, 2000, function ($e) {
                if ($e instanceof ConnectionException) {
                    return true;
                }

                return $e instanceof RequestException
                    && in_array($e->response?->status(), [429, 500, 502, 503], true);
            }, throw: false)
            ->post(rtrim($this->cfg['base_url'] ?? 'https://api.openai.com', '/').'/v1/chat/completions', [
                'model' => $this->cfg['model'] ?? 'gpt-5',
                'max_completion_tokens' => (int) ($this->cfg['max_tokens'] ?? 2000),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $this->userBlock($payload)],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'articulo',
                        'strict' => true,
                        'schema' => $this->schema(),
                    ],
                ],
            ]);

        if (in_array($resp->status(), [401, 403], true)) {
            throw new RuntimeException(
                "OpenAI rechazó la autenticación (HTTP {$resp->status()}): OPENAI_API_KEY "
                .'inválida o sin permisos. Revisa .env en el servidor y, si la config está '
                .'cacheada, ejecuta `php artisan config:clear` y `php artisan queue:restart`.'
            );
        }

        $resp->throw();

        $mensaje = $resp->json('choices.0.message', []);

        if (! empty($mensaje['refusal'])) {
            throw new RuntimeException('OpenAI rechazó la petición: '.$mensaje['refusal']);
        }

        $contenido = $mensaje['content'] ?? null;
        $datos = is_string($contenido) ? json_decode($contenido, true) : null;

        if (! is_array($datos)) {
            throw new RuntimeException('Respuesta de OpenAI sin JSON estructurado.');
        }

        return $datos;
    }

    /** @return array<string,mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'dek', 'body', 'suggested_section', 'confidence'],
            'properties' => [
                'title' => ['type' => 'string'],
                'dek' => ['type' => 'string'],
                'body' => ['type' => 'string', 'description' => 'Markdown en español, sobrio'],
                'suggested_section' => ['type' => 'string', 'enum' => ['rankings', 'alertas', 'informes', 'perfiles']],
                'confidence' => ['type' => 'number'],
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
