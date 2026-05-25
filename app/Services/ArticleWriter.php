<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Support\Str;

/**
 * Aísla la creación del Article (modelo de la capa editorial) a partir de un candidato
 * y la respuesta de Claude. Crea siempre un BORRADOR para revisión humana.
 */
class ArticleWriter
{
    private const MAPA_ENTIDAD = [
        'empresa' => Empresa::class,
        'organismo' => Organismo::class,
        'licitacion' => Licitacion::class,
        'categoria' => Categoria::class,
    ];

    /**
     * @param  object  $candidate  fila de story_candidates (payload/entidades en JSON)
     * @param  array{title:string,dek:string,body:string,suggested_section:string,confidence:float}  $result
     */
    public function crearBorrador(object $candidate, array $result): Article
    {
        $payload = $this->decode($candidate->payload);
        $entidades = $this->decode($candidate->entidades);

        $seccion = ArticleSection::tryFrom($result['suggested_section'] ?? '')
            ?? ArticleSection::tryFrom($candidate->seccion)
            ?? ArticleSection::Informes;

        $article = Article::create([
            'title' => $result['title'],
            'slug' => $this->slugUnico($result['title']),
            'dek' => $result['dek'] ?? null,
            'body' => $result['body'] ?? '',
            'body_format' => 'markdown',
            'section' => $seccion->value,
            'status' => ArticleStatus::Draft->value,
            'author_name' => 'Redacción I-Licitaciones (IA)',
            'source_snapshot' => $payload, // cifras congeladas que se inyectaron al modelo
        ]);

        $this->relacionar($article, $entidades);

        return $article;
    }

    private function relacionar(Article $article, array $entidades): void
    {
        $porTipo = [];
        foreach ($entidades as $e) {
            $tipo = $e['type'] ?? null;
            if (! isset(self::MAPA_ENTIDAD[$tipo], $e['id'])) {
                continue;
            }
            $porTipo[$tipo][(int) $e['id']] = [
                'role' => ($e['primary'] ?? false) ? 'protagonista' : 'mencionado',
                'is_primary' => (bool) ($e['primary'] ?? false),
            ];
        }

        foreach ($porTipo as $tipo => $attach) {
            $relacion = match ($tipo) {
                'empresa' => 'empresas',
                'organismo' => 'organismos',
                'licitacion' => 'licitaciones',
                'categoria' => 'categorias',
            };
            $article->{$relacion}()->sync($attach);
        }
    }

    private function slugUnico(string $title): string
    {
        $base = Str::slug($title) ?: 'analisis';
        $slug = $base;
        $i = 2;

        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }
}
