<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant Context Resolver
 * 
 * Tenant metadata'sını çözen servis. Middleware'in doğrudan DB'ye gitmesini önler.
 * 
 * Mimari Kararlar:
 * - Read-through cache pattern: önce cache'e bak, yoksa DB'den al ve cache'e yaz
 * - Kısa TTL (5 dakika): tenant verisi seyrek değişir ama stale data riski minimize edilir
 * - Cache miss durumunda DB fallback: sistem her zaman çalışır
 * - Invalidation: tenant güncellendiğinde cache temizlenir
 * 
 * Trade-off:
 * - Kazanç: DB read load düşer, connection pool baskısı azalır
 * - Risk: Stale tier/active flag (TTL ile minimize edildi)
 */
class TenantResolver
{
    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'tenant_context:';

    /**
     * Cache TTL (saniye) - 5 dakika
     * Kısa tutuldu çünkü tier/active değişikliği hemen yansımalı
     */
    private const CACHE_TTL = 300;

    /**
     * Tenant ID'den context çöz
     * 
     * Read-through cache pattern:
     * 1. Cache'e bak
     * 2. Varsa döndür
     * 3. Yoksa DB'den al, cache'e yaz, döndür
     */
    public function resolve(int $tenantId): ?TenantContext
    {
        $cacheKey = self::CACHE_PREFIX . $tenantId;

        // Aşama 2: Cache'den oku (read-through)
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return TenantContext::fromArray($cached);
        }

        // Cache miss: DB'den al
        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            return null;
        }

        $context = TenantContext::fromModel($tenant);

        // Cache'e yaz
        Cache::put($cacheKey, $context->toArray(), self::CACHE_TTL);

        return $context;
    }

    /**
     * Tenant güncellendiğinde cache'i invalidate et
     * 
     * Kullanım: Tenant model observer'dan çağrılabilir
     * Örn: TenantObserver::updated() içinde
     */
    public function invalidate(int $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . $tenantId);
    }

    /**
     * Tüm tenant cache'ini temizle
     * 
     * Kullanım: Deployment sonrası veya schema değişikliğinde
     */
    public function flushAll(): void
    {
        // Not: Bu yöntem cache driver'a göre farklı çalışabilir
        // Redis'te pattern silme, file cache'te dosya silme gerekir
        // Şimdilik tek tenant invalidation tercih edilmeli
        Cache::flush();
    }
}
