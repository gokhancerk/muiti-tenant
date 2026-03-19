<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bu sınıf rotaya ulaşmadan önce isteği durdurur. Gerekli kısıtlama (Header) yoksa, framework'ün Controller'ı boot etmesine izin vermeden isteği 400 Bad Request ile reddeder (Erken çıkış / Fail-fast prensibi).
 */
class TenantIdentificationMiddleware
{
    public function __construct(private TenantManager $tenantManager)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        $tenandId = $request->header('X-Tenant-ID');

        // Kısıtlama: Tenant ID yoksa sistemi yorma, I/O maliyeti yaratma.
        if (! $tenandId) {
            return response()->json([
            'error' => 'Access Denied: X-Tenant-ID header missing'
            ],400);
        }
        // State'i mevcut HTTP isteği için manager'a enjekte ediyoruz.
        $this->tenantManager->setTenantId((int)$tenandId);

        return $next($request);
    }
}
