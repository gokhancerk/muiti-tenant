<?php

namespace App\Providers;

use App\Services\ApplicationLoggerService as ServicesApplicationLoggerService;
use App\Services\CheckoutSessionService as ServicesCheckoutSessionService;
use App\Services\JsonPlaceHolderService;
use App\Services\TaxCalculatorService;
use App\Services\TenantManager;
use App\Services\Tenant\TenantResolver;
use App\Models\Tenant;
use App\Observers\TenantObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //bu sınıfları Container'a nasıl davranacaklarını belirterek kaydedin.
        
        // Her istendiğinde yeni nesne (Farklı RAM adresi)
        $this->app->bind(TaxCalculatorService::class);

        // Bir kez üret, hep aynı nesneyi ver (Aynı RAM adresi)
        $this->app->singleton(ServicesApplicationLoggerService::class);

        $this->app->singleton(JsonPlaceHolderService::class);

        // İstek boyunca aynı, yeni istekte yeni nesne (Octane'de fark yaratır)
        $this->app->scoped(ServicesCheckoutSessionService::class);

        // TenantManager: İstek boyunca aynı instance (rate limiting ve middleware için kritik)
        $this->app->scoped(TenantManager::class);

        // TenantResolver: Singleton - tenant metadata çözümlemesi (cache-aware)
        // Stateless servis, her request'te aynı instance kullanılabilir
        $this->app->singleton(TenantResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tenant Observer: Cache invalidation için
        Tenant::observe(TenantObserver::class);

        // Okuma İşlemleri İçin Politika (Düşük Maliyetli I/O)
        RateLimiter::for('tenant.read', function (Request $request) {
            return $this->resolveTenantLimit($request, 'read', [
                'free' => 60,
                'pro' => 300,
            ]);
        });

        // Yazma İşlemleri İçin Politika (Yüksek Maliyetli I/O, Daha Sıkı Limit)
        RateLimiter::for('tenant.write', function (Request $request) {
            return $this->resolveTenantLimit($request, 'write', [
                'free' => 20,
                'pro' => 100,
            ]);
        });
    }


    /**
     * Sınırlama mantığını soyutlayan çekirdek fonksiyon (DRY prensibi)
     */
    private function resolveTenantLimit(Request $request, string $actionType, array $limits): Limit
    {
        $tenantManager = app(TenantManager::class);

        // Kısıtlama 1: Fail-fast. Tenant yoksa işlemi doğrudan kilitle.
        if (! $tenantManager->hasTenant()) {
            abort(403, 'Sistem Kısıtlaması: Tenant context bulunamadı.');
        }

        $tenantId = $tenantManager->getTenantId();
        $tier = $tenantManager->getTier();
        
        // Güvenlik: Geçersiz bir plan gelirse en düşük limite zorla (Fallback)
        $allowedRequests = $limits[$tier] ?? $limits['free'];
        
        // Rota adını al, yoksa action type kullan
        $routeName = $request->route() ? $request->route()->getName() : $actionType;

        // Benzersiz Anahtar: tenant:{id}:route:{name}
        $rateLimitKey = "tenant:{$tenantId}:route:{$routeName}";

        return Limit::perMinute($allowedRequests)
            ->by($rateLimitKey)
            ->response(function (Request $request, array $headers) use ($tier) {
                return response()->json([
                    'error' => 'Too Many Requests',
                    'message' => "Tenant ({$tier}) request quota exceeded. Resource fairness policy activated.",
                ], 429, $headers);
            });


    }
}
