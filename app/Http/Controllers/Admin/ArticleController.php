<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Categoria;
use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use App\Support\ArticleRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function dashboard(): View
    {
        $conteos = [
            'draft' => Article::where('status', ArticleStatus::Draft->value)->count(),
            'review' => Article::where('status', ArticleStatus::Review->value)->count(),
            'published' => Article::where('status', ArticleStatus::Published->value)->count(),
        ];

        $pendientes = Article::whereIn('status', [ArticleStatus::Draft->value, ArticleStatus::Review->value])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('conteos', 'pendientes'));
    }

    public function index(): View
    {
        $status = request('status');

        $articles = Article::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.articles.index', compact('articles', 'status'));
    }

    public function create(): View
    {
        $article = new Article(['body_format' => 'markdown', 'section' => ArticleSection::Informes->value, 'status' => ArticleStatus::Draft->value]);

        return view('admin.articles.form', $this->formData($article));
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = new Article;
        $this->fill($article, $request);
        $article->author_id ??= Auth::id();
        $article->author_name ??= Auth::user()->name;
        $article->save();

        $this->syncEntidades($article, $request);
        $this->flush();

        return redirect()->route('admin.articles.edit', $article)->with('ok', 'Artículo creado.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', $this->formData($article));
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->fill($article, $request);
        $article->save();

        $this->syncEntidades($article, $request);
        $this->flush();

        return redirect()->route('admin.articles.edit', $article)->with('ok', 'Cambios guardados.');
    }

    public function preview(Article $article, ArticleRenderer $renderer): View
    {
        // Reusa la plantilla pública; el controlador público permite ver borradores a editores.
        $article->load(['empresas', 'organismos', 'licitaciones', 'categorias', 'author']);
        $bodyHtml = $renderer->build($article); // sin cache, para ver cambios al instante

        return view('analisis.show', compact('article', 'bodyHtml'))->with('preview', true);
    }

    public function publish(Article $article): RedirectResponse
    {
        $article->status = ArticleStatus::Published;
        $article->published_at ??= now();
        $article->save();
        $this->flush();

        return back()->with('ok', 'Artículo publicado.');
    }

    public function unpublish(Article $article): RedirectResponse
    {
        $article->status = ArticleStatus::Draft;
        $article->save();
        $this->flush();

        return back()->with('ok', 'Artículo despublicado.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();
        $this->flush();

        return redirect()->route('admin.articles.index')->with('ok', 'Artículo eliminado.');
    }

    // --- helpers -----------------------------------------------------------

    private function formData(Article $article): array
    {
        return [
            'article' => $article,
            'secciones' => ArticleSection::cases(),
            'estados' => ArticleStatus::cases(),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'entidadesActuales' => $article->exists ? [
                'empresas' => $article->empresas()->pluck('empresas.id')->implode(', '),
                'organismos' => $article->organismos()->pluck('organismos.id')->implode(', '),
                'licitaciones' => $article->licitaciones()->pluck('licitacions.id')->implode(', '),
                'categorias' => $article->categorias()->pluck('categorias.id')->implode(', '),
            ] : ['empresas' => '', 'organismos' => '', 'licitaciones' => '', 'categorias' => ''],
        ];
    }

    private function fill(Article $article, StoreArticleRequest $request): void
    {
        $article->fill($request->only([
            'title', 'dek', 'body_format', 'section', 'status',
            'provincia', 'categoria_id', 'meta_title', 'meta_description', 'og_image',
        ]));
        $article->body = $request->bodyForStorage();
        $article->data = $request->dataForStorage();

        if (! $article->slug || $article->isDirty('title')) {
            $article->slug = $this->slugUnico($request->input('title'), $article->id);
        }

        // published_at coherente con el estado.
        if ($article->status === ArticleStatus::Published && $article->published_at === null) {
            $article->published_at = now();
        }
    }

    private function slugUnico(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'analisis';
        $slug = $base;
        $i = 2;

        while (Article::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereNot('id', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function syncEntidades(Article $article, StoreArticleRequest $request): void
    {
        $article->empresas()->sync($request->entityIds('empresas'));
        $article->organismos()->sync($request->entityIds('organismos'));
        $article->licitaciones()->sync($request->entityIds('licitaciones'));
        $article->categorias()->sync($request->entityIds('categorias'));
    }

    private function flush(): void
    {
        // Listados públicos y bloque del home se cachean por clave no enumerable; flush global
        // (publicar/editar es poco frecuente) — mismo criterio que RecalcularEstadisticas.
        Cache::flush();
    }
}
