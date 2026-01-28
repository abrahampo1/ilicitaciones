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
        Schema::create('adjudicacions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->double('importe')->nullable();
            $table->double('importe_final')->nullable();

            $table->foreignId('licitacion_id')->nullable()->constrained('licitacions')->onDelete('set null');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');

            $table->string('urgencia')->nullable();
            $table->string('tipo_procedimiento')->nullable();
            $table->text('descripcion')->nullable();


            $table->date('fecha_adjudicacion')->nullable();
            $table->date('fecha_comienzo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjudicacions');
    }
};