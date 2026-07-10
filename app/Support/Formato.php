<?php

namespace App\Support;

/**
 * Formateo de importes para las vistas públicas (es-ES).
 */
class Formato
{
    /**
     * Importe compacto legible: "1,2 billones €", "12.400 millones €", "850.000 €".
     */
    public static function eurosCompactos(float $importe): string
    {
        $abs = abs($importe);

        if ($abs >= 1e12) {
            return self::decimal($importe / 1e12).' billones €';
        }

        if ($abs >= 1e6) {
            return number_format($importe / 1e6, 0, ',', '.').' millones €';
        }

        return number_format($importe, 0, ',', '.').' €';
    }

    private static function decimal(float $n): string
    {
        $s = number_format($n, 1, ',', '.');

        return str_ends_with($s, ',0') ? substr($s, 0, -2) : $s;
    }
}
