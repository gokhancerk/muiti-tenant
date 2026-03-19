<?php

namespace App\Providers;

use App\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

/**
 * Bu provider, TenantManager sınıfını Container'a scoped olarak kaydeder. Böylece her yeni HTTP isteğinde sınıf sıfırlanır, veri kirliliği (state pollution) engellenir.
 */
class TenantProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Nesne sadece mevcut HTTP isteği boyunca tekil (singleton) kalır.

        $this->app->scoped(TenantManager::class, function($app){
            return new TenantManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Global Query Scope tanımlamaları (2. aşama) burada yapılacak.
        
    }
}
