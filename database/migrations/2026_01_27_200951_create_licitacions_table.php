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
        Schema::create('licitacions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('titulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('identificador')->nullable();
            $table->string('url')->nullable();
            $table->string('id_url')->nullable();
            $table->string('estado')->nullable();

            $table->double('importe_total')->nullable();
            $table->double('importe_final')->nullable();
            $table->double('importe_estimado')->nullable();

            $table->date('fecha_contratacion')->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();

            $table->foreignId('organismo_id')->nullable()->constrained('organismos')->onDelete('set null');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');

            $table->json('datos_raiz')->nullable();

            // el identificador debe ser un indice unico
            $table->unique('identificador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitacions');
    }
};
