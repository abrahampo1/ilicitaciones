<?php

namespace App\Console\Commands;

use App\Analysis\DetectorRegistry;
use App\Analysis\StoryCandidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Recorre los detectores y persiste los candidatos de historia de forma idempotente
 * (por signature). Barato y sin IA: solo SQL. Apto para --sync diario.
 */
class DetectarHistorias extends Command
{
    protected $signature = 'app:detectar-historias
                            {--tipo=* : Ejecutar solo estos detectores (por tipo)}';

    protected $description = 'Detecta candidatos de historia en los datos (sin IA)';

    public function handle(DetectorRegistry $registry): int
    {
        $t0 = microtime(true);

        $detectores = $registry->activos();
        if ($filtro = $this->option('tipo')) {
            $detectores = array_filter($detectores, fn ($d) => in_array($d->tipo(), $filtro, true));
        }

        if (empty($detectores)) {
            $this->components->warn('No hay detectores activos que ejecutar.');

            return self::SUCCESS;
        }

        $this->dibujarCabecera(count($detectores));

        $resumen = [];
        $tot = ['cand' => 0, 'nuevo' => 0, 'actualizado' => 0, 'omitido' => 0, 'error' => 0];

        foreach ($detectores as $detector) {
            $tipo = $detector->tipo();
            // Marca el detector en curso (se completa la línea al terminar). Útil cuando
            // un detector tarda sobre una base grande.
            $this->output->write(sprintf('  <fg=gray>%-20s</> ', $tipo));

            $c = ['cand' => 0, 'nuevo' => 0, 'actualizado' => 0, 'omitido' => 0];
            $start = microtime(true);

            try {
                foreach ($detector->detect() as $candidato) {
                    $c['cand']++;
                    $c[$this->persistir($candidato)]++;
                }
            } catch (Throwable $e) {
                $tot['error']++;
                $this->output->writeln('<fg=red>ERROR</> '.$e->getMessage());
                $resumen[] = [$tipo, '—', '—', '—', '—', 'error'];

                continue;
            }

            $dt = microtime(true) - $start;
            $this->output->writeln(sprintf(
                '<info>%4d</> cand · <fg=green>%d</> nuevos · <fg=yellow>%d</> act · <fg=gray>%d omit · %s</>',
                $c['cand'], $c['nuevo'], $c['actualizado'], $c['omitido'], $this->dur($dt),
            ));

            foreach (['cand', 'nuevo', 'actualizado', 'omitido'] as $k) {
                $tot[$k] += $c[$k];
            }
            $resumen[] = [$tipo, $c['cand'], $c['nuevo'], $c['actualizado'], $c['omitido'], $this->dur($dt)];
        }

        $this->dibujarResumen($resumen, $tot, microtime(true) - $t0);

        return self::SUCCESS;
    }

    private function dibujarCabecera(int $nDetectores): void
    {
        $this->newLine();
        $this->components->info(sprintf('Detección de historias · %d detectores', $nDetectores));
        $this->components->twoColumnDetail('<fg=gray>Licitaciones</>', number_format(DB::table('licitacions')->count(), 0, ',', '.'));
        $this->components->twoColumnDetail('<fg=gray>Adjudicaciones</>', number_format(DB::table('adjudicacions')->count(), 0, ',', '.'));
        $this->components->twoColumnDetail('<fg=gray>Agregados dimensión</>', number_format(DB::table('agregados_dimension')->count(), 0, ',', '.'));
        $this->newLine();
    }

    private function dibujarResumen(array $resumen, array $tot, float $elapsed): void
    {
        $this->newLine();
        $this->table(
            ['Detector', 'Cand.', 'Nuevos', 'Actual.', 'Omit.', 'Tiempo'],
            $resumen,
        );

        $this->components->twoColumnDetail(
            '<options=bold>Totales</>',
            sprintf('%d candidatos · %d nuevos · %d actualizados · %d omitidos', $tot['cand'], $tot['nuevo'], $tot['actualizado'], $tot['omitido']),
        );
        if ($tot['error'] > 0) {
            $this->components->twoColumnDetail('<fg=red>Detectores con error</>', (string) $tot['error']);
        }

        // Estado actual de la cola de candidatos (lo que verá generar-borradores).
        $estados = DB::table('story_candidates')
            ->selectRaw('estado, COUNT(*) as n')
            ->groupBy('estado')
            ->pluck('n', 'estado');

        $this->newLine();
        $this->components->info('Cola de candidatos por estado');
        foreach (['pendiente', 'generando', 'generado', 'descartado', 'error'] as $estado) {
            $this->components->twoColumnDetail("<fg=gray>{$estado}</>", (string) ($estados[$estado] ?? 0));
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=gray>Tiempo total · memoria pico</>',
            sprintf('%s · %s MB', $this->dur($elapsed), number_format(memory_get_peak_usage(true) / 1048576, 1)),
        );
    }

    private function dur(float $segundos): string
    {
        return $segundos >= 60
            ? sprintf('%dm %02ds', (int) ($segundos / 60), (int) $segundos % 60)
            : sprintf('%.1fs', $segundos);
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
