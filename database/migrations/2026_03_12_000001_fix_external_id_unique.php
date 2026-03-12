<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->unique('external_id');
        });
    }
};
