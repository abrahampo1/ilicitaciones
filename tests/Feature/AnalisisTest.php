<?php

use App\Models\Article;
use App\Models\Empresa;
use App\Models\Enums\ArticleSection;

it('el índice muestra los publicados y oculta los borradores', function () {
    Article::factory()->published()->create(['title' => 'Analisis Publicado Uno']);
    Article::factory()->draft()->create(['title' => 'Borrador Oculto Uno']);

    $this->get('/analisis')
        ->assertStatus(200)
        ->assertSee('Analisis Publicado Uno')
        ->assertDontSee('Borrador Oculto Uno');
});

it('la ficha de un artículo publicado responde 200 por slug', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Concentracion en Sanidad',
        'slug' => 'concentracion-en-sanidad',
        'body' => 'Cuerpo del analisis con datos.',
    ]);

    $this->get('/analisis/'.$article->slug)
        ->assertStatus(200)
        ->assertSee('Concentracion en Sanidad')
        ->assertSee('Cuerpo del analisis con datos.');
});

it('un borrador devuelve 404 al público', function () {
    $article = Article::factory()->draft()->create(['slug' => 'borrador-secreto']);

    $this->get('/analisis/'.$article->slug)->assertStatus(404);
});

it('la sección filtra por tipo y valida el enum', function () {
    Article::factory()->published()->create([
        'title' => 'Alerta de Urgencia',
        'section' => ArticleSection::Alertas->value,
    ]);
    Article::factory()->published()->create([
        'title' => 'Ranking Anual',
        'section' => ArticleSection::Rankings->value,
    ]);

    $this->get('/analisis/seccion/alertas')
        ->assertStatus(200)
        ->assertSee('Alerta de Urgencia')
        ->assertDontSee('Ranking Anual');

    $this->get('/analisis/seccion/inexistente')->assertStatus(404);
});

it('el feed RSS lista los artículos publicados', function () {
    Article::factory()->published()->create(['title' => 'Noticia En El Feed']);

    $res = $this->get('/analisis/feed.xml')->assertStatus(200);

    expect($res->headers->get('Content-Type'))->toContain('application/rss+xml');
    $res->assertSee('Noticia En El Feed');
});

it('renderiza shortcodes de gráfico desde el payload data', function () {
    $article = Article::factory()->published()->create([
        'body' => "Introduccion.\n\n[[chart:ventas]]",
        'data' => ['ventas' => [
            'type' => 'bar',
            'items' => [['label' => '2024', 'value' => 1234567]],
        ]],
    ]);

    $this->get('/analisis/'.$article->slug)
        ->assertStatus(200)
        ->assertSee('1.234.567€'); // bar component formatea la cifra del payload
});

it('la ficha enlaza con las entidades relacionadas', function () {
    $empresa = Empresa::factory()->create(['nombre' => 'Constructora Ejemplo SA']);
    $article = Article::factory()->published()->create();
    $article->empresas()->attach($empresa->id, ['role' => 'protagonista', 'is_primary' => true]);

    $this->get('/analisis/'.$article->slug)
        ->assertStatus(200)
        ->assertSee('Constructora Ejemplo SA')
        ->assertSee(route('empresa.show', $empresa->id));
});

it('el home muestra el bloque de análisis recientes', function () {
    Article::factory()->published()->create(['title' => 'Recien Publicado Home']);

    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Recien Publicado Home');
});
