<?php

use Illuminate\Support\Facades\Route;

Route::get('/', Modules\Contratos\Livewire\Dashboard::class)->name('home');
Route::get('/contratos', Modules\Contratos\Livewire\ContratosIndex::class)->name('contratos.index');
Route::get('/contrato/{id}', Modules\Contratos\Livewire\ContratoDetalle::class)->name('contratos.show');
Route::get('/organismos', Modules\Contratos\Livewire\OrganismosIndex::class)->name('organismos.index');
Route::get('/organismo/{id}', Modules\Contratos\Livewire\OrganismoDetalle::class)->name('organismos.show');
Route::get('/empresas', Modules\Contratos\Livewire\EmpresasIndex::class)->name('empresas.index');
Route::get('/empresa/{id}', Modules\Contratos\Livewire\EmpresaDetalle::class)->name('empresas.show');
