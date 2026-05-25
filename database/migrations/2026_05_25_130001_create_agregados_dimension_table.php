<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregados precomputados por dimensión: generaliza inversiones_anuales a CPV,
     * provincia y pares (empresa↔organismo, empresa↔CPV). Alimenta rankings, informes
     * y los detectores de concentración. Lo recalcula RecalcularAgregadosDimension.
     */
    public function up(): void
    {
        Schema::create('agregados_dimension', function (Blueprint $table) {
            $table->id();
            $table->string('dimension', 20);          // cpv | provincia | empresa_organismo | empresa_cpv
            $table->string('key_a', 191);             // categoria_id | provincia | empresa_id
            $table->string('key_b', 191)->nullable(); // organismo_id | categoria_id (pares)
            $table->smallInteger('year')->nullable(); // null = histórico total
            $table->double('total_importe')->default(0);
            $table->unsignedInteger('num_adjudicaciones')->default(0);
            $table->unsignedInteger('num_licitaciones')->default(0);
            $table->unsignedInteger('num_empresas')->default(0); // distinct (HHI/concentración)

            $table->unique(['dimension', 'key_a', 'key_b', 'year'], 'idx_agreg_unique');
            $table->index(['dimension', 'year'], 'idx_agreg_dim_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agregados_dimension');
    }
};
