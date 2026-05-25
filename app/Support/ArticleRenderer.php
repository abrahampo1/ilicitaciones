<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use League\CommonMark\CommonMarkConverter;

/**
 * Renderiza el cuerpo de un artículo a HTML:
 *  1. markdown -> HTML (CommonMark) salvo que body_format sea 'html' (ya saneado).
 *  2. resuelve shortcodes [[chart:clave]] / [[table:clave]] / [[kpi:clave]] /
 *     [[callout:clave]] inyectando componentes Blade renderizados desde data[clave].
 *
 * El HTML de los componentes es de confianza (lo genera el servidor). El HTML del
 * cuerpo en formato 'html' se sanea en el Form Request antes de guardar.
 */
class ArticleRenderer
{
    /** shortcode => carpeta de componente por defecto */
    private const SHORTCODES = [
        'chart' => 'chart',
        'table' => 'data.table',
        'kpi' => 'data.kpi',
        'callout' => 'article.callout',
    ];

    public function render(Article $article): string
    {
        $key = "article_body_{$article->id}_".optional($article->updated_at)->timestamp;

        return Cache::remember($key, 86400, fn () => $this->build($article));
    }

    public function build(Article $article): string
    {
        $body = (string) $article->body;
        $data = $article->data ?? [];

        $html = $article->body_format === 'html'
            ? $body
            : (new CommonMarkConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]))
                ->convert($body)
                ->getContent();

        return $this->resolveShortcodes($html, $data);
    }

    private function resolveShortcodes(string $html, array $data): string
    {
        // Captura el shortcode opcionalmente envuelto por CommonMark en <p>...</p>.
        $pattern = '/(?:<p>\s*)?\[\[(chart|table|kpi|callout):([a-z0-9_\-]+)\]\](?:\s*<\/p>)?/i';

        return preg_replace_callback($pattern, function ($m) use ($data) {
            $tipo = strtolower($m[1]);
            $clave = $m[2];
            $spec = $data[$clave] ?? null;

            if (! is_array($spec)) {
                return ''; // clave inexistente: se omite en silencio
            }

            return $this->renderComponent($tipo, $spec);
        }, $html) ?? $html;
    }

    private function renderComponent(string $tipo, array $spec): string
    {
        $view = self::SHORTCODES[$tipo];

        // Para 'chart' el subtipo (bar|ranking) decide el componente final.
        if ($tipo === 'chart') {
            $sub = in_array($spec['type'] ?? 'bar', ['bar', 'ranking'], true) ? $spec['type'] : 'bar';
            $view = "chart.{$sub}";
        }

        $viewName = "components.{$view}";

        if (! View::exists($viewName)) {
            return '';
        }

        return View::make($viewName, ['spec' => $spec])->render();
    }
}
