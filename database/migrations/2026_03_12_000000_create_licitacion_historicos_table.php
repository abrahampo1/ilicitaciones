<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licitacion_id')->constrained('licitacions')->cascadeOnDelete();
            $table->string('estado')->nullable();
            $table->string('status_code', 10)->nullable();
            $table->decimal('importe_con_iva', 15, 2)->nullable();
            $table->decimal('importe_sin_iva', 15, 2)->nullable();
            $table->decimal('valor_estimado', 15, 2)->nullable();
            $table->decimal('importe_adjudicacion_sin_iva', 15, 2)->nullable();
            $table->decimal('importe_adjudicacion_con_iva', 15, 2)->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();
            $table->json('datos_raiz')->nullable();
            $table->json('cambios')->nullable();
            $table->timestamps();

            $table->index(['licitacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_historicos');
    }
};
