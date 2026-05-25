<?php

namespace App\Models\Concerns;

use App\Models\Article;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Da a una entidad (Empresa, Organismo, Licitacion, Categoria) la relación inversa
 * con los artículos que la mencionan. Alimenta el bloque "Mencionado en estos
 * análisis" de las fichas.
 */
trait HasArticles
{
    public function articles(): MorphToMany
    {
        return $this->morphToMany(Article::class, 'entity', 'article_entity')
            ->withPivot('role', 'is_primary');
    }
}
