<?php

use App\Models\Article;
use App\Models\Enums\ArticleSection;
use App\Models\Enums\ArticleStatus;
use App\Models\User;

function editor(): User
{
    return User::factory()->create(['is_editor' => true]);
}

it('redirige al login a los invitados', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('bloquea a usuarios sin permiso editorial', function () {
    $user = User::factory()->create(['is_editor' => false]);

    $this->actingAs($user)->get('/admin')->assertStatus(403);
});

it('un editor accede al panel', function () {
    $this->actingAs(editor())->get('/admin')->assertStatus(200);
});

it('un editor crea un artículo y se genera el slug', function () {
    $this->actingAs(editor())->post('/admin/articulos', [
        'title' => 'Mi Primer Analisis',
        'dek' => 'Una entradilla.',
        'body' => 'Cuerpo en markdown.',
        'body_format' => 'markdown',
        'section' => ArticleSection::Informes->value,
        'status' => ArticleStatus::Draft->value,
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Mi Primer Analisis');
    expect($article)->not->toBeNull()
        ->and($article->slug)->toBe('mi-primer-analisis')
        ->and($article->status)->toBe(ArticleStatus::Draft);
});

it('publicar deja el artículo visible y fija published_at', function () {
    $article = Article::factory()->draft()->create(['slug' => 'para-publicar']);

    $this->actingAs(editor())->post("/admin/articulos/{$article->id}/publicar")->assertRedirect();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at)->not->toBeNull();

    $this->get('/analisis/para-publicar')->assertStatus(200);
});

it('despublicar oculta el artículo del público', function () {
    $article = Article::factory()->published()->create(['slug' => 'para-despublicar']);

    $this->actingAs(editor())->post("/admin/articulos/{$article->id}/despublicar")->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);

    auth()->logout(); // el público (no editor) no debe verlo
    $this->get('/analisis/para-despublicar')->assertStatus(404);
});

it('un editor puede previsualizar un borrador', function () {
    $article = Article::factory()->draft()->create(['title' => 'Borrador En Preview']);

    $this->actingAs(editor())
        ->get("/admin/articulos/{$article->id}/preview")
        ->assertStatus(200)
        ->assertSee('Borrador En Preview');
});

it('actualizar sincroniza entidades relacionadas', function () {
    $article = Article::factory()->draft()->create();
    $empresa = \App\Models\Empresa::factory()->create();

    $this->actingAs(editor())->put("/admin/articulos/{$article->id}", [
        'title' => $article->title,
        'body_format' => 'markdown',
        'section' => $article->section->value,
        'status' => ArticleStatus::Draft->value,
        'empresas' => (string) $empresa->id,
    ])->assertRedirect();

    expect($article->empresas()->count())->toBe(1);
});
