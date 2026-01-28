<?php

use App\Models\Licitacion;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/licitacion/{id}', function () {
    return view('licitacion', [
        'licitacion' => Licitacion::findOrFail(request('id')),
    ]);
})->name('licitacion.show');