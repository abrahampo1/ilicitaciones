<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LicitacionController;
use App\Http\Controllers\OrganismoController;
use App\Http\Controllers\WrappedController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/licitacion/{id}', [LicitacionController::class, 'show'])->name('licitacion.show');
Route::get('/organismos', [OrganismoController::class, 'index'])->name('organismos');
Route::get('/organismo/{id}', [OrganismoController::class, 'show'])->name('organismo.show');
Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas');
Route::get('/empresa/{id}', [EmpresaController::class, 'show'])->name('empresa.show');

// Wrapped anual: resumen del gasto público de cada año en formato historias.
Route::get('/wrapped', [WrappedController::class, 'index'])->name('wrapped.index');
Route::get('/wrapped/{year}', [WrappedController::class, 'show'])->where('year', '[1-9][0-9]{3}')->name('wrapped.show');

// Periódico de datos (análisis). El orden importa: rutas específicas antes del slug.
Route::get('/analisis', [AnalisisController::class, 'index'])->name('analisis.index');
Route::get('/analisis/feed.xml', [FeedController::class, 'rss'])->name('analisis.feed');
Route::get('/analisis/seccion/{section}', [AnalisisController::class, 'section'])->name('analisis.section');
Route::get('/analisis/{article:slug}', [AnalisisController::class, 'show'])->name('analisis.show');

// Panel de redacción (editor único).
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.attempt');

    Route::middleware(['auth', 'can:manage-articles'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminArticleController::class, 'dashboard'])->name('dashboard');
        Route::get('articulos', [AdminArticleController::class, 'index'])->name('articles.index');
        Route::get('articulos/crear', [AdminArticleController::class, 'create'])->name('articles.create');
        Route::post('articulos', [AdminArticleController::class, 'store'])->name('articles.store');
        Route::get('articulos/{article:id}/editar', [AdminArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articulos/{article:id}', [AdminArticleController::class, 'update'])->name('articles.update');
        Route::get('articulos/{article:id}/preview', [AdminArticleController::class, 'preview'])->name('articles.preview');
        Route::post('articulos/{article:id}/publicar', [AdminArticleController::class, 'publish'])->name('articles.publish');
        Route::post('articulos/{article:id}/despublicar', [AdminArticleController::class, 'unpublish'])->name('articles.unpublish');
        Route::delete('articulos/{article:id}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');
    });
});
