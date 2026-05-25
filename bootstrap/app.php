<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Mantiene los agregados frescos aunque no se importe (red de seguridad).
        // Se ejecuta en el propio proceso del scheduler (--sync): no requiere worker
        // de cola, solo el cron de `schedule:run`.
        $schedule->command('app:recalcular-estadisticas --sync')
            ->hourly()
            ->withoutOverlapping();

        // Periódico de datos: agregados + detección (--sync, sin worker) y generación
        // de borradores con IA (encola jobs; requiere worker de cola).
        $schedule->command('app:recalcular-agregados-dimension --sync')
            ->dailyAt('03:30')->withoutOverlapping();
        $schedule->command('app:detectar-historias')
            ->dailyAt('04:00')->withoutOverlapping();
        $schedule->command('app:generar-borradores')
            ->dailyAt('04:30')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Invitados al panel van al login de la redacción (no existe ruta 'login' global).
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
