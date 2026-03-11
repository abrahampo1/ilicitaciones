<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Covering indexes for Dashboard aggregate queries:
     * - topOrganismos: GROUP BY organismo_id + SUM(importe_total)
     * - topEmpresas: GROUP BY empresa_id + SUM(importe)
     * - latestDate: ORDER BY fecha_actualizacion DESC (covering)
     * - OrganismosIndex: filter by provincia
     */
    public function up(): void
    {
        // Covering index for topOrganismos JOIN query
        if (!$this->indexExists('licitacions', 'idx_licitacions_organismo_importe')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->index(['organismo_id', 'importe_total'], 'idx_licitacions_organismo_importe');
            });
        }

        // Covering index for topEmpresas JOIN query
        if (!$this->indexExists('adjudicacions', 'idx_adjudicacions_empresa_importe')) {
            Schema::table('adjudicacions', function (Blueprint $table) {
                $table->index(['empresa_id', 'importe'], 'idx_adjudicacions_empresa_importe');
            });
        }

        // Descending index for latestDate ORDER BY (replaces simple index if needed)
        if (!$this->indexExists('licitacions', 'idx_licitacions_fecha_act_desc')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->index(['fecha_actualizacion'], 'idx_licitacions_fecha_act_desc');
            });
        }

        // Index for OrganismosIndex provincia filter
        if (!$this->indexExists('organismos', 'idx_organismos_provincia')) {
            Schema::table('organismos', function (Blueprint $table) {
                $table->index('provincia', 'idx_organismos_provincia');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('licitacions', 'idx_licitacions_organismo_importe')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->dropIndex('idx_licitacions_organismo_importe');
            });
        }

        if ($this->indexExists('adjudicacions', 'idx_adjudicacions_empresa_importe')) {
            Schema::table('adjudicacions', function (Blueprint $table) {
                $table->dropIndex('idx_adjudicacions_empresa_importe');
            });
        }

        if ($this->indexExists('licitacions', 'idx_licitacions_fecha_act_desc')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->dropIndex('idx_licitacions_fecha_act_desc');
            });
        }

        if ($this->indexExists('organismos', 'idx_organismos_provincia')) {
            Schema::table('organismos', function (Blueprint $table) {
                $table->dropIndex('idx_organismos_provincia');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }
};
