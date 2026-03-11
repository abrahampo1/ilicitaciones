<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entidades_presupuestarias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->index();
            $table->string('nombre', 500);
            $table->string('codigo_ine', 20)->nullable()->unique();
            $table->string('codigo_ccaa', 5)->nullable()->index();
            $table->string('provincia', 100)->nullable();
            $table->unsignedInteger('poblacion')->nullable();
            $table->string('codigo_dir3', 50)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'codigo_ccaa']);
            $table->index(['nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entidades_presupuestarias');
    }
};
