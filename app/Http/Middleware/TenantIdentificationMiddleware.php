<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use App\Services\Tenant\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Identification Middleware
 * 
 * Rotaya ulaşmadan önce isteği durdurur. Gerekli kısıtlama (Header) yoksa, 
 * framework'ün Controller'ı boot etmesine izin vermeden isteği reddeder.
 * 
 * Mimari:
 * - TenantResolver: Tenant metadata'ı çözer (cache + DB fallback)
 * - TenantManager: Request scope'unda tenant state taşır
 * - TenantContext DTO: İmmutable tenant verisi
 * 
 * Bu yapı sayesinde:
 * - Doğrudan DB bağımlılığı yok (resolver arkasında)
 * - Cache katmanı transparent şekilde çalışır
 * - Source of truth değişse middleware değişmez
 */
class TenantIdentificationMiddleware
{
    public function __construct(
        private TenantManager $tenantManager,
        private TenantResolver $tenantResolver,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        $tenantId = $request->header('X-Tenant-ID');

        // Kısıtlama 1: Tenant ID yoksa sistemi yorma (fail-fast)
        if (! $tenantId) {
            return response()->json([
                'error' => 'Access Denied: X-Tenant-ID header missing'
            ], 400);
        }

        // Resolver üzerinden tenant context'ı çöz (cache-aware)
        $context = $this->tenantResolver->resolve((int) $tenantId);

        // Kısıtlama 2: Geçersiz veya inaktif tenant
        if ($context === null || !$context->isActive) {
            return response()->json([
                'error' => 'Access Denied: Invalid or inactive tenant'
            ], 403);
        }

        // State'i mevcut HTTP isteği için manager'a enjekte et
        $this->tenantManager->setContext($context);

        return $next($request);
    }
}
