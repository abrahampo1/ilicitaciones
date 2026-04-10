<?php

use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LicitacionController;
use App\Http\Controllers\OrganismoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/licitacion/{id}', [LicitacionController::class, 'show'])->name('licitacion.show');
Route::get('/organismos', [OrganismoController::class, 'index'])->name('organismos');
Route::get('/organismo/{id}', [OrganismoController::class, 'show'])->name('organismo.show');
Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas');
Route::get('/empresa/{id}', [EmpresaController::class, 'show'])->name('empresa.show');
