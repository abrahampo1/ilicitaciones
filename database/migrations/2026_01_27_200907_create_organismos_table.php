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
        Schema::create('organismos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('nombre')->nullable()->index();
            $table->string('identificador')->nullable()->index();

            $table->string('direccion')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('provincia')->nullable();
            $table->string('pais')->nullable();

            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->string('contacto_fax')->nullable();
            $table->string('contacto_email')->nullable();

            $table->string('sitio_web')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organismos');
    }
};
