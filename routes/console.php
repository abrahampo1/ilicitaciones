<?php

use Illuminate\Support\Facades\Schedule;

// Sync contratos del mes actual cada noche a las 02:00
Schedule::command('contracts:sync')->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/contracts-sync.log'))
    ->onFailure(function () {
        logger()->error('contracts:sync falló en el cron nocturno');
    });

// Sync PGE del año actual cada lunes a las 03:00
Schedule::command('budgets:sync-pge')->weeklyOn(1, '03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/budgets-sync.log'))
    ->onFailure(function () {
        logger()->error('budgets:sync-pge falló en el cron semanal');
    });

// Sync municipales registrados cada lunes a las 04:00
Schedule::command('budgets:sync-municipal --all')->weeklyOn(1, '04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/budgets-sync.log'))
    ->onFailure(function () {
        logger()->error('budgets:sync-municipal falló en el cron semanal');
    });
