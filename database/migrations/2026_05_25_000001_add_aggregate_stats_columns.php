<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Precomputed aggregate columns + supporting tables.
     *
     * Las páginas de listado (empresas, organismos, home) calculaban SUM/COUNT
     * con GROUP BY en cada request. Aquí denormalizamos esos agregados a columnas
     * indexadas que un job recalcula tras cada importación, de modo que el listado
     * pasa a ser un simple ORDER BY sobre índice.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->double('total_importe')->default(0)->index('idx_empresas_total_importe');
            $table->unsignedInteger('total_adjudicaciones')->default(0);
        });

        Schema::table('organismos', function (Blueprint $table) {
            $table->double('total_importe')->default(0)->index('idx_organismos_total_importe');
            $table->unsignedInteger('total_licitaciones')->default(0);
        });

        // Inversión anual precalculada por entidad (usada en las fichas show).
        Schema::create('inversiones_anuales', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 20); // 'empresa' | 'organismo'
            $table->unsignedBigInteger('entity_id');
            $table->smallInteger('year');
            $table->double('total')->default(0);

            $table->unique(['entity_type', 'entity_id', 'year'], 'idx_inv_anual_unique');
            $table->index(['entity_type', 'entity_id'], 'idx_inv_anual_entity');
        });

        // Estadísticas globales (home): una sola fila clave -> valor JSON.
        Schema::create('estadisticas', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->json('valor')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropIndex('idx_empresas_total_importe');
            $table->dropColumn(['total_importe', 'total_adjudicaciones']);
        });

        Schema::table('organismos', function (Blueprint $table) {
            $table->dropIndex('idx_organismos_total_importe');
            $table->dropColumn(['total_importe', 'total_licitaciones']);
        });

        Schema::dropIfExists('inversiones_anuales');
        Schema::dropIfExists('estadisticas');
    }
};
