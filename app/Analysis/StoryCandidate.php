<?php

namespace App\Analysis;

/**
 * Resultado de un detector: una historia candidata con sus datos exactos (payload),
 * las entidades implicadas y una firma estable para deduplicar.
 */
class StoryCandidate
{
    /**
     * @param  string  $tipo  p.ej. 'adjudicatario_unico'
     * @param  string  $seccion  rankings|alertas|informes|perfiles
     * @param  string  $signature  identidad estable (sin cifras volátiles)
     * @param  float  $score  0..100
     * @param  array<string,mixed>  $payload  cifras exactas que verá el modelo
     * @param  list<array{type:string,id:int,role?:string,primary?:bool}>  $entidades
     */
    public function __construct(
        public string $tipo,
        public string $seccion,
        public string $signature,
        public float $score,
        public array $payload,
        public array $entidades = [],
    ) {}

    public static function firma(string ...$partes): string
    {
        return hash('sha256', implode('|', $partes));
    }
}
