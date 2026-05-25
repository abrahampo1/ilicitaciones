<?php

namespace App\Analysis\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Helpers para SQL portable entre MySQL (producción) y SQLite (dev/tests).
 * Mismo criterio que App\Jobs\RecalcularEstadisticas.
 */
trait DualDatabase
{
    protected function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /** Expresión para extraer el año de una columna fecha. */
    protected function yearExpr(string $column): string
    {
        return $this->isMysql()
            ? "YEAR({$column})"
            : "CAST(strftime('%Y', {$column}) AS INTEGER)";
    }
}
