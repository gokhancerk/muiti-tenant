<?php

namespace App\Providers;

use App\Services\ApplicationLoggerService as ServicesApplicationLoggerService;
use App\Services\CheckoutSessionService as ServicesCheckoutSessionService;
use App\Services\JsonPlaceHolderService;
use App\Services\TaxCalculatorService;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
