<?php

namespace App\Services;

/**
 * Defensa en profundidad anti-alucinación: cada cifra "grande" (típicamente un importe)
 * del cuerpo redactado debe corresponder a un número presente en el payload oficial.
 * Las cifras pequeñas (años, porcentajes, recuentos) se permiten sin exigir match.
 */
class FactChecker
{
    /** Por debajo de este valor no se exige correspondencia (años, %, recuentos). */
    private const UMBRAL_VERIFICACION = 3000;

    /** @param  array<string,mixed>  $payload */
    public function verificar(string $body, array $payload): bool
    {
        $permitidos = $this->numerosPayload($payload);

        foreach ($this->numerosTexto($body) as $n) {
            if ($n <= self::UMBRAL_VERIFICACION) {
                continue;
            }
            if (! $this->coincide($n, $permitidos)) {
                return false;
            }
        }

        return true;
    }

    /** Primer número del cuerpo sin respaldo (para diagnóstico), o null si todo cuadra. */
    public function primeraCifraInvalida(string $body, array $payload): ?float
    {
        $permitidos = $this->numerosPayload($payload);

        foreach ($this->numerosTexto($body) as $n) {
            if ($n > self::UMBRAL_VERIFICACION && ! $this->coincide($n, $permitidos)) {
                return $n;
            }
        }

        return null;
    }

    /** @return list<float> */
    private function numerosPayload(array $payload): array
    {
        $nums = [];

        array_walk_recursive($payload, function ($v) use (&$nums) {
            if (is_int($v) || is_float($v)) {
                $nums[] = (float) $v;
            } elseif (is_string($v) && is_numeric($v)) {
                $nums[] = (float) $v;
            }
        });

        return $nums;
    }

    /** @return list<float> Números detectados en el texto (formato español). */
    private function numerosTexto(string $body): array
    {
        preg_match_all('/\d[\d.,]*/', $body, $m);

        $nums = [];
        foreach ($m[0] as $token) {
            $token = rtrim($token, '.,');

            if (str_contains($token, ',')) {
                // ',' decimal, '.' miles
                $norm = str_replace('.', '', $token);
                $norm = str_replace(',', '.', $norm);
            } else {
                // solo puntos => miles
                $norm = str_replace('.', '', $token);
            }

            if (is_numeric($norm)) {
                $nums[] = (float) $norm;
            }
        }

        return $nums;
    }

    /** @param  list<float>  $permitidos */
    private function coincide(float $n, array $permitidos): bool
    {
        foreach ($permitidos as $p) {
            if (abs($n - $p) <= max(1.0, abs($p) * 0.01)) {
                return true;
            }
            if (round($n) === round($p)) {
                return true;
            }
        }

        return false;
    }
}
