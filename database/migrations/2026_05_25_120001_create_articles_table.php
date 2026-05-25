<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capa editorial: artículos del periódico de datos. Un artículo cruza con las
     * entidades existentes (empresa/organismo/licitación/CPV) vía article_entity.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('dek')->nullable();          // entradilla / subtítulo
            $table->longText('body')->nullable();        // markdown o html
            $table->string('body_format', 12)->default('markdown'); // markdown|html
            $table->string('section', 30)->index();      // ArticleSection
            $table->string('status', 12)->default('draft'); // ArticleStatus
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();   // byline denormalizado
            $table->string('provincia')->nullable()->index();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->json('data')->nullable();            // payloads de charts/tablas/kpis
            $table->json('source_snapshot')->nullable(); // cifras congeladas (auditoría)
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at'], 'idx_articles_status_published');
            $table->index(['section', 'status', 'published_at'], 'idx_articles_section_status_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
