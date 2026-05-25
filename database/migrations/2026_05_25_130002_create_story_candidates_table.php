<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidatos de historia detectados en los datos. `signature` (estable, sin cifras
     * volátiles) da idempotencia; `payload` es la verdad factual que se inyecta al
     * prompt de Claude; `cooldown_until` evita regenerar la misma historia.
     */
    public function up(): void
    {
        Schema::create('story_candidates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('tipo', 40);   // adjudicatario_unico | concentracion | ...
            $table->string('seccion', 20); // rankings | alertas | informes | perfiles
            $table->string('signature', 64)->unique();
            $table->double('score')->default(0);
            $table->json('payload')->nullable();
            $table->json('entidades')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente|generando|generado|descartado|error
            $table->unsignedBigInteger('article_id')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('cooldown_until')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->text('last_error')->nullable();

            $table->index(['estado', 'score'], 'idx_sc_estado_score');
            $table->index(['tipo', 'cooldown_until'], 'idx_sc_tipo_cooldown');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_candidates');
    }
};
