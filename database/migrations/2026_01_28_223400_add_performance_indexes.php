<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Añade índices para optimizar las consultas más comunes:
     * - Ordenación por fecha_actualizacion
     * - Búsquedas por estado
     * - Foreign keys (ya tienen índice automático)
     * - Búsquedas en empresas/adjudicaciones
     */
    public function up(): void
    {
        // Índices para licitacions
        Schema::table('licitacions', function (Blueprint $table) {
            $table->index('fecha_actualizacion', 'idx_licitacions_fecha_actualizacion');
            $table->index('estado', 'idx_licitacions_estado');
            $table->index('created_at', 'idx_licitacions_created_at');
            $table->index('importe_total', 'idx_licitacions_importe_total');
        });

        // Índices para empresas (usados en updateOrCreate/upsert)
        Schema::table('empresas', function (Blueprint $table) {
            $table->index('identificador', 'idx_empresas_identificador');
            $table->index('nombre', 'idx_empresas_nombre');
            // Índice compuesto para búsquedas combinadas
            $table->index(['identificador', 'nombre'], 'idx_empresas_identificador_nombre');
        });

        // Índice compuesto para organismos (ya tienen índices simples, añadir compuesto)
        Schema::table('organismos', function (Blueprint $table) {
            $table->index(['identificador', 'nombre'], 'idx_organismos_identificador_nombre');
        });

        // Índices para adjudicacions
        Schema::table('adjudicacions', function (Blueprint $table) {
            // Índice compuesto para upsert (licitacion_id + empresa_id)
            $table->unique(['licitacion_id', 'empresa_id'], 'idx_adjudicacions_licitacion_empresa');
            $table->index('importe', 'idx_adjudicacions_importe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropIndex('idx_licitacions_fecha_actualizacion');
            $table->dropIndex('idx_licitacions_estado');
            $table->dropIndex('idx_licitacions_created_at');
            $table->dropIndex('idx_licitacions_importe_total');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropIndex('idx_empresas_identificador');
            $table->dropIndex('idx_empresas_nombre');
            $table->dropIndex('idx_empresas_identificador_nombre');
        });

        Schema::table('organismos', function (Blueprint $table) {
            $table->dropIndex('idx_organismos_identificador_nombre');
        });

        Schema::table('adjudicacions', function (Blueprint $table) {
            $table->dropUnique('idx_adjudicacions_licitacion_empresa');
            $table->dropIndex('idx_adjudicacions_importe');
        });
    }
};
