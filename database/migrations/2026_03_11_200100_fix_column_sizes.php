<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organismos', function (Blueprint $table) {
            $table->string('nombre', 500)->nullable()->change();
            $table->string('contacto_nombre', 500)->nullable()->change();
            $table->string('sitio_web', 1000)->nullable()->change();
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nombre', 500)->nullable()->change();
        });

        Schema::table('licitacions', function (Blueprint $table) {
            $table->string('identificador', 500)->nullable()->change();
            $table->string('url', 1000)->nullable()->change();
            $table->string('id_url', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        // No reducimos — podría truncar datos existentes
    }
};
