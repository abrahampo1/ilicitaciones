<?php

namespace App\Analysis;

class Scoring
{
    public static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /** Puntúa el importe en escala logarítmica respecto a un umbral. */
    public static function importe(float $importe, float $umbral, float $factor, float $max): float
    {
        if ($importe <= 0 || $umbral <= 0) {
            return 0;
        }

        return self::clamp(log10(max(1, $importe / $umbral)) * $factor, 0, $max);
    }
}
