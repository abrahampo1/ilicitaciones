<?php

namespace App\Models;

use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'dek',
        'body',
        'body_format',
        'section',
        'status',
        'published_at',
        'author_id',
        'author_name',
        'provincia',
        'categoria_id',
        'data',
        'source_snapshot',
        'meta_title',
        'meta_description',
        'og_image',
    ];

    protected function casts(): array
    {
        return [
            'section' => ArticleSection::class,
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
            'data' => 'array',
            'source_snapshot' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param  Builder<Article>  $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published->value)
            ->where('published_at', '<=', now());
    }

    /** @param  Builder<Article>  $query */
    public function scopeSection(Builder $query, ArticleSection $section): Builder
    {
        return $query->where('section', $section->value);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function empresas(): MorphToMany
    {
        return $this->morphedByMany(Empresa::class, 'entity', 'article_entity')
            ->withPivot('role', 'is_primary');
    }

    public function organismos(): MorphToMany
    {
        return $this->morphedByMany(Organismo::class, 'entity', 'article_entity')
            ->withPivot('role', 'is_primary');
    }

    public function licitaciones(): MorphToMany
    {
        return $this->morphedByMany(Licitacion::class, 'entity', 'article_entity')
            ->withPivot('role', 'is_primary');
    }

    public function categorias(): MorphToMany
    {
        return $this->morphedByMany(Categoria::class, 'entity', 'article_entity')
            ->withPivot('role', 'is_primary');
    }

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(now());
    }
}
