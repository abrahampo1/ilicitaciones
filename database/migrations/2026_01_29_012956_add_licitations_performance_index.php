<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->index('organismo_id', 'idx_licitacions_organismo_id');
            $table->index('categoria_id', 'idx_licitacions_categoria_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropIndex('idx_licitacions_organismo_id');
            $table->dropIndex('idx_licitacions_categoria_id');
        });
    }
};
