<?php

namespace App\Jobs;

use App\Services\ArticleWriter;
use App\Services\FactChecker;
use App\Services\LLM\DrafterFactory;
use App\Services\PromptBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Redacta el borrador de un candidato con el proveedor LLM elegido (Claude u OpenAI),
 * verifica las cifras y crea el Article. Idempotente: solo procesa 'generando'.
 */
class GenerarBorradorArticulo implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public array $backoff = [60, 300];

    public function __construct(public int $candidateId, public ?string $provider = null) {}

    public function uniqueId(): string
    {
        return 'borrador-'.$this->candidateId;
    }

    public function handle(DrafterFactory $drafters, PromptBuilder $prompts, FactChecker $checker, ArticleWriter $writer): void
    {
        $candidate = DB::table('story_candidates')->where('id', $this->candidateId)->first();

        if (! $candidate || $candidate->estado !== 'generando') {
            return; // ya procesado o cancelado
        }

        $payload = json_decode($candidate->payload, true) ?: [];

        $result = $drafters->make($this->provider)->redactar($prompts->system($candidate->tipo), $payload);

        // Anti-alucinación: ninguna cifra grande del cuerpo puede faltar del payload.
        if (! $checker->verificar($result['body'] ?? '', $payload)) {
            $invalida = $checker->primeraCifraInvalida($result['body'] ?? '', $payload);
            $this->marcarError($candidate->id, "Cifra no verificada: {$invalida}");

            return;
        }

        $confianzaMin = (float) config('periodico.generacion.confidence_min');
        if ((float) ($result['confidence'] ?? 0) < $confianzaMin) {
            DB::table('story_candidates')->where('id', $candidate->id)->update([
                'estado' => 'pendiente',
                'last_error' => 'confidence baja; requiere revisión manual',
                'updated_at' => now(),
            ]);

            return;
        }

        $article = $writer->crearBorrador($candidate, $result);

        DB::table('story_candidates')->where('id', $candidate->id)->update([
            'estado' => 'generado',
            'article_id' => $article->id,
            'generated_at' => now(),
            'cooldown_until' => now()->addDays($this->cooldownDias($candidate->seccion)),
            'last_error' => null,
            'updated_at' => now(),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        $this->marcarError($this->candidateId, $e?->getMessage() ?? 'error desconocido', true);
    }

    private function marcarError(int $id, string $mensaje, bool $incrementar = false): void
    {
        DB::table('story_candidates')->where('id', $id)->update(array_filter([
            'estado' => 'error',
            'last_error' => $mensaje,
            'updated_at' => now(),
        ]));

        if ($incrementar) {
            DB::table('story_candidates')->where('id', $id)->increment('intentos');
        }
    }

    private function cooldownDias(string $seccion): int
    {
        return (int) (config("periodico.cooldown_dias.{$seccion}") ?? 30);
    }
}
