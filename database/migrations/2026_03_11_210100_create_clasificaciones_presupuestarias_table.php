<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clasificaciones_presupuestarias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->index();
            $table->string('codigo', 30);
            $table->string('codigo_padre', 30)->nullable();
            $table->tinyInteger('nivel');
            $table->string('nombre', 500);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['tipo', 'codigo']);
            $table->index(['tipo', 'codigo_padre']);
            $table->index(['tipo', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clasificaciones_presupuestarias');
    }
};
