<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Enums\ArticleSection;
use App\Support\ArticleRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalisisController extends Controller
{
    public function index(Request $request): View
    {
        $page = (int) $request->input('page', 1);

        $articles = cache()->remember("analisis_index_{$page}", 600, function () {
            return Article::published()
                ->with('author')
                ->latest('published_at')
                ->paginate(12);
        });

        $articles->withQueryString();

        return view('analisis.index', [
            'articles' => $articles,
            'secciones' => ArticleSection::cases(),
            'seccionActiva' => null,
        ]);
    }

    public function section(string $section, Request $request): View
    {
        $seccion = ArticleSection::tryFrom($section);
        abort_if($seccion === null, 404);

        $page = (int) $request->input('page', 1);

        $articles = cache()->remember("analisis_seccion_{$seccion->value}_{$page}", 600, function () use ($seccion) {
            return Article::published()
                ->section($seccion)
                ->with('author')
                ->latest('published_at')
                ->paginate(12);
        });

        $articles->withQueryString();

        return view('analisis.section', [
            'articles' => $articles,
            'secciones' => ArticleSection::cases(),
            'seccionActiva' => $seccion,
        ]);
    }

    public function show(Article $article, ArticleRenderer $renderer): View
    {
        // Editores pueden previsualizar borradores; el público solo ve publicados.
        abort_unless($article->isPublished() || Gate::allows('manage-articles'), 404);

        $article->load(['empresas', 'organismos', 'licitaciones', 'categorias', 'author']);

        $bodyHtml = $renderer->render($article);

        return view('analisis.show', compact('article', 'bodyHtml'));
    }
}
