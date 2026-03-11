<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas_presupuestarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entidad_id')->constrained('entidades_presupuestarias')->onDelete('cascade');
            $table->smallInteger('ejercicio')->index();
            $table->string('tipo_presupuesto', 10)->index();
            $table->foreignId('clasificacion_organica_id')->nullable()->constrained('clasificaciones_presupuestarias')->onDelete('set null');
            $table->foreignId('clasificacion_funcional_id')->nullable()->constrained('clasificaciones_presupuestarias')->onDelete('set null');
            $table->foreignId('clasificacion_economica_id')->nullable()->constrained('clasificaciones_presupuestarias')->onDelete('set null');
            $table->string('codigo_organica', 30)->nullable()->index();
            $table->string('codigo_funcional', 30)->nullable()->index();
            $table->string('codigo_economica', 30)->nullable()->index();
            $table->decimal('credito_inicial', 17, 2)->nullable();
            $table->decimal('credito_definitivo', 17, 2)->nullable();
            $table->decimal('credito_actual', 17, 2)->nullable();
            $table->string('fuente', 30)->index();
            $table->string('external_id', 500)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique([
                'entidad_id', 'ejercicio', 'tipo_presupuesto',
                'codigo_organica', 'codigo_funcional', 'codigo_economica', 'fuente',
            ], 'partidas_unique_composite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas_presupuestarias');
    }
};
