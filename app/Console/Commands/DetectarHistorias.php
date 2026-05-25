<?php

namespace App\Console\Commands;

use App\Analysis\DetectorRegistry;
use App\Analysis\StoryCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recorre los detectores y persiste los candidatos de historia de forma idempotente
 * (por signature). Barato y sin IA: solo SQL. Apto para --sync diario.
 */
class DetectarHistorias extends Command
{
    protected $signature = 'app:detectar-historias';

    protected $description = 'Detecta candidatos de historia en los datos (sin IA)';

    public function handle(DetectorRegistry $registry): int
    {
        $nuevos = 0;
        $actualizados = 0;
        $omitidos = 0;

        foreach ($registry->activos() as $detector) {
            foreach ($detector->detect() as $candidato) {
                match ($this->persistir($candidato)) {
                    'nuevo' => $nuevos++,
                    'actualizado' => $actualizados++,
                    default => $omitidos++,
                };
            }
        }

        $this->info("Detección completa: {$nuevos} nuevos, {$actualizados} actualizados, {$omitidos} omitidos.");

        return self::SUCCESS;
    }

    private function persistir(StoryCandidate $c): string
    {
        $existente = DB::table('story_candidates')->where('signature', $c->signature)->first();

        $datos = [
            'tipo' => $c->tipo,
            'seccion' => $c->seccion,
            'score' => $c->score,
            'payload' => json_encode($c->payload, JSON_UNESCAPED_UNICODE),
            'entidades' => json_encode($c->entidades, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        if ($existente === null) {
            DB::table('story_candidates')->insert($datos + [
                'signature' => $c->signature,
                'estado' => 'pendiente',
                'created_at' => now(),
            ]);

            return 'nuevo';
        }

        $reciclable = in_array($existente->estado, ['generado', 'error'], true)
            && ($existente->cooldown_until === null || $existente->cooldown_until <= now()->toDateTimeString());

        if ($existente->estado === 'pendiente' || $reciclable) {
            DB::table('story_candidates')->where('id', $existente->id)->update(
                $datos + ($reciclable ? ['estado' => 'pendiente', 'article_id' => null] : [])
            );

            return 'actualizado';
        }

        return 'omitido'; // generando, o generado/error aún en cooldown, o descartado
    }
}
