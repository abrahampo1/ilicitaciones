<?php

use App\Jobs\RecalcularEstadisticas;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Rellena los agregados precomputados (columnas, inversiones_anuales,
     * estadisticas) en el momento de migrar.
     *
     * Las columnas añadidas en la migración anterior nacen a 0; sin esto, los
     * listados de empresas saldrían vacíos (se filtran por total_adjudicaciones>0)
     * y los organismos a 0€ hasta que un worker procesara el job en cola. Ejecutarlo
     * aquí garantiza datos correctos tras `php artisan migrate` sin depender de la cola.
     */
    public function up(): void
    {
        (new RecalcularEstadisticas)->handle();
    }

    public function down(): void
    {
        // Nada que revertir: solo recalcula datos derivados.
    }
};
