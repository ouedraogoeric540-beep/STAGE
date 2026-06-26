<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withCommands([
        // ← Enregistrer la commande ici
        \App\Console\Commands\TerminerEvenementsExpires::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->alias([
            'role'          => \App\Http\Middleware\CheckRole::class,
            'compte.bloque' => \App\Http\Middleware\CheckCompteBloque::class,
        ]);
        $middleware->appendToGroup('api', \App\Http\Middleware\VerifierEvenementsExpires::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();