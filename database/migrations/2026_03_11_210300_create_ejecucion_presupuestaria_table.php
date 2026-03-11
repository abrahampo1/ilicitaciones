<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejecucion_presupuestaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas_presupuestarias')->onDelete('cascade');
            $table->string('periodo', 10);
            $table->decimal('credito_autorizado', 17, 2)->nullable();
            $table->decimal('credito_dispuesto', 17, 2)->nullable();
            $table->decimal('obligaciones', 17, 2)->nullable();
            $table->decimal('pagos_propuestos', 17, 2)->nullable();
            $table->decimal('pagos_realizados', 17, 2)->nullable();
            $table->decimal('remanentes', 17, 2)->nullable();
            $table->decimal('porcentaje_ejecucion', 5, 2)->nullable();
            $table->string('fuente', 30);
            $table->timestamps();

            $table->unique(['partida_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecucion_presupuestaria');
    }
};
