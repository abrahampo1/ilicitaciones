<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivote polimórfico artículo <-> entidad (empresa, organismo, licitación,
     * categoría). Un solo pivote en lugar de una tabla por tipo: permite el lookup
     * inverso uniforme ("mencionado en estos análisis") en cada ficha.
     */
    public function up(): void
    {
        Schema::create('article_entity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('entity_type');               // App\Models\Empresa, ...
            $table->unsignedBigInteger('entity_id');
            $table->string('role', 20)->nullable();      // protagonista|mencionado|dato
            $table->boolean('is_primary')->default(false);

            $table->unique(['article_id', 'entity_type', 'entity_id'], 'idx_article_entity_unique');
            $table->index(['entity_type', 'entity_id'], 'idx_article_entity_morph');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_entity');
    }
};
