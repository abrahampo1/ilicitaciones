<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice para las consultas por rango de fecha del Wrapped anual: sin él, cada
     * build() del wrapped es un full scan sobre adjudicacions.
     */
    public function up(): void
    {
        Schema::table('adjudicacions', function (Blueprint $table) {
            $table->index('fecha_adjudicacion', 'idx_adjudicacions_fecha_adjudicacion');
        });
    }

    public function down(): void
    {
        Schema::table('adjudicacions', function (Blueprint $table) {
            $table->dropIndex('idx_adjudicacions_fecha_adjudicacion');
        });
    }
};
