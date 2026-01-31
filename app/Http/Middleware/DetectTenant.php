<?php

namespace App\Http\Middleware;

use App\Exceptions\InActiveUserException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

class DetectTenant
{
    use UsesTenantConnection;

    /**
     * Handle an incoming request.
     * @throws NoCurrentTenant
     * @throws InActiveUserException
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to detect tenant by domain/subdomain
        $host = $request->getHost(); // tenant1.crm.com
        $tenant = Tenant::where('domain', $host)->first();

        // If not found, try X-Tenant-Id header
        if (!$tenant && $request->hasHeader('X-Tenant-Id')) {
            $tenantId = $request->header('X-Tenant-Id');
            $tenant = Tenant::find($tenantId);
        }

        // If still not found, throw exception
        if (!$tenant) {
            throw new NoCurrentTenant('Tenant not found for this request.');
        }

        if (!$tenant->is_active) {
            throw new InActiveUserException(__('api.account_not_active'));
        }

        // Make tenant current
        $tenant->makeCurrent();

        // Override default connection globally
        DB::purge('tenant');
        config(['database.connections.tenant.database' => $tenant->database]);
        config(['database.default' => 'tenant']);
        DB::reconnect('tenant');

        return $next($request);
    }
}
