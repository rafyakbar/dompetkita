<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Sanitize stale url.intended pointing to non-existent tenant
        $this->cleanStaleIntendedUrl($request);

        // 2. Validate tenant parameter on route if present
        $tenantParam = $request->route()?->parameter('tenant');

        if (! $tenantParam) {
            return $next($request);
        }

        $tenantSlug = is_string($tenantParam) ? $tenantParam : ($tenantParam->slug ?? null);

        if (! $tenantSlug) {
            return $next($request);
        }

        $tenant = Account::where('slug', $tenantSlug)->first();
        $user = auth()->user() ?? $request->user();

        // Tenant slug does not exist in database
        if (! $tenant) {
            return $this->handleInvalidTenant($request, $user);
        }

        return $next($request);
    }

    protected function cleanStaleIntendedUrl(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $intended = $request->session()->get('url.intended');

        if (! $intended || ! is_string($intended)) {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! $path || ! str_starts_with($path, '/app/')) {
            return;
        }

        // Extract tenant segment: /app/{slug}/...
        $segments = explode('/', trim($path, '/'));

        if (count($segments) >= 2) {
            $slug = $segments[1];
            $reserved = ['login', 'register', 'new', 'password-reset'];

            if (! in_array($slug, $reserved, true) && ! Account::where('slug', $slug)->exists()) {
                $request->session()->forget('url.intended');
            }
        }
    }

    protected function handleInvalidTenant(Request $request, mixed $user): Response
    {
        if ($request->hasSession()) {
            $request->session()->forget('url.intended');
        }

        if ($user instanceof HasTenants) {
            $panel = Filament::getCurrentOrDefaultPanel();
            $defaultTenant = $user->getDefaultTenant($panel);

            if ($defaultTenant && $defaultTenant->slug) {
                return redirect("/app/{$defaultTenant->slug}");
            }

            return redirect('/app/new');
        }

        return redirect('/app/login');
    }
}
