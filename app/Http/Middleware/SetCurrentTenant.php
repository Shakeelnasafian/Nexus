<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentTenant;
use App\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Resolve tenant from the X-Tenant-Slug header (dev/API) or the subdomain,
        // falling back to the first tenant in the database for single-tenant development.
        $slug = $request->header('X-Tenant-Slug')
            ?? $this->subdomainFromHost($request->getHost());

        $tenant = $slug
            ? Tenant::where('slug', $slug)->first()
            : Tenant::first();

        if ($tenant) {
            $this->currentTenant->set($tenant);
        }

        return $next($request);
    }

    private function subdomainFromHost(string $host): ?string
    {
        // Strip port if present (e.g. localhost:8000 → localhost)
        $host = strtok($host, ':');

        // Skip IP addresses — they have no subdomain
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $parts = explode('.', $host);

        // Only treat the first segment as a tenant slug when there are 3+ parts
        // (e.g. acme.nexus.test → 'acme'). Ignore localhost / bare domains.
        return count($parts) >= 3 ? $parts[0] : null;
    }
}
