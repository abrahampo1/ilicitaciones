<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('presupuestos')->group(function () {
    Route::get('/', Modules\Presupuestos\Livewire\PresupuestosDashboard::class)->name('presupuestos.index');
    Route::get('/explorador', Modules\Presupuestos\Livewire\PresupuestosExplorador::class)->name('presupuestos.explorador');
    Route::get('/entidad/{id}', Modules\Presupuestos\Livewire\EntidadPresupuestariaDetalle::class)->name('presupuestos.entidad');
    Route::get('/comparador', Modules\Presupuestos\Livewire\ComparadorMunicipal::class)->name('presupuestos.comparador');
    Route::get('/ejecucion', Modules\Presupuestos\Livewire\EjecucionTimeline::class)->name('presupuestos.ejecucion');
});
