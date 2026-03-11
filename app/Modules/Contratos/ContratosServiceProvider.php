<?php

namespace Modules\Contratos;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ContratosServiceProvider extends ServiceProvider
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
        Livewire::component('contratos::dashboard', \Modules\Contratos\Livewire\Dashboard::class);
        Livewire::component('contratos::contratos-index', \Modules\Contratos\Livewire\ContratosIndex::class);
        Livewire::component('contratos::contrato-detalle', \Modules\Contratos\Livewire\ContratoDetalle::class);
        Livewire::component('contratos::organismos-index', \Modules\Contratos\Livewire\OrganismosIndex::class);
        Livewire::component('contratos::organismo-detalle', \Modules\Contratos\Livewire\OrganismoDetalle::class);
        Livewire::component('contratos::empresas-index', \Modules\Contratos\Livewire\EmpresasIndex::class);
        Livewire::component('contratos::empresa-detalle', \Modules\Contratos\Livewire\EmpresaDetalle::class);

        // Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Contratos\Console\SyncContracts::class,
                \Modules\Contratos\Console\ImportarCategorias::class,
            ]);
        }
    }
}
