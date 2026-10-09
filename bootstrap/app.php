<?php

use App\Http\Middleware\ValidateTenantAccess;
use App\Models\Account;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: ValidateTenantAccess::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($e->getModel() === Account::class && $request->is('app/*')) {
                if ($request->hasSession()) {
                    $request->session()->forget('url.intended');
                }

                $user = auth()->user();

                if ($user instanceof HasTenants) {
                    $defaultTenant = $user->getDefaultTenant(Filament::getCurrentOrDefaultPanel());

                    if ($defaultTenant && $defaultTenant->slug) {
                        return redirect("/app/{$defaultTenant->slug}");
                    }

                    return redirect('/app/new');
                }

                return redirect('/app/login');
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
