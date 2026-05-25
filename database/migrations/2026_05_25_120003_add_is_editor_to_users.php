<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Editor único del periódico: ability `manage-articles` se concede si is_editor. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_editor')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_editor');
        });
    }
};
