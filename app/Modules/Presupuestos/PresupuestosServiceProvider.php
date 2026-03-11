<?php

namespace Modules\Presupuestos;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PresupuestosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        // Livewire components
        Livewire::component('presupuestos::dashboard', \Modules\Presupuestos\Livewire\PresupuestosDashboard::class);
        Livewire::component('presupuestos::explorador', \Modules\Presupuestos\Livewire\PresupuestosExplorador::class);
        Livewire::component('presupuestos::entidad-detalle', \Modules\Presupuestos\Livewire\EntidadPresupuestariaDetalle::class);
        Livewire::component('presupuestos::comparador', \Modules\Presupuestos\Livewire\ComparadorMunicipal::class);
        Livewire::component('presupuestos::ejecucion', \Modules\Presupuestos\Livewire\EjecucionTimeline::class);

        // Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Presupuestos\Console\SeedClasificaciones::class,
                \Modules\Presupuestos\Console\SyncPge::class,
                \Modules\Presupuestos\Console\SyncMunicipal::class,
            ]);
        }
    }
}
