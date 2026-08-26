<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // §6.11 : une détention/mesure arrivant à échéance sans décision est
        // signalée « en priorité absolue » — vérification chaque minute.
        $schedule->command('gav:verifier-echeances')->everyMinute()->withoutOverlapping();

        // §6.6, §6.11 : même exigence de signalement en priorité absolue que
        // la GAV, mais à l'échelle de jours plutôt que de minutes — une
        // vérification horaire suffit très largement.
        $schedule->command('instruction:verifier-echeances-detention')->hourly()->withoutOverlapping();

        // §6.10, §6.11 : constat des réhabilitations de plein droit — rien
        // n'y est urgent à la minute près, une vérification quotidienne
        // suffit.
        $schedule->command('casier:verifier-rehabilitations')->daily()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
