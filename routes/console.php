<?php

use Illuminate\Support\Facades\Schedule;

// Sync contratos del mes actual cada noche a las 02:00
Schedule::command('contracts:sync')->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/contracts-sync.log'))
    ->onFailure(function () {
        logger()->error('contracts:sync falló en el cron nocturno');
    });
