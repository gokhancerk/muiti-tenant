<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Services\Tenant\TenantResolver;

/**
 * Tenant Model Observer
 * 
 * Tenant verisi değiştiğinde cache invalidation yapar.
 * Bu sayede stale data riski minimize edilir.
 * 
 * Önemli: Observer AppServiceProvider'da boot() içinde kaydedilmeli.
 */
class TenantObserver
{
    public function __construct(
        private TenantResolver $tenantResolver
    ) {}

    /**
     * Tenant güncellendiğinde cache'i invalidate et
     */
    public function updated(Tenant $tenant): void
    {
        $this->tenantResolver->invalidate($tenant->id);
    }

    /**
     * Tenant silindiğinde cache'i invalidate et
     */
    public function deleted(Tenant $tenant): void
    {
        $this->tenantResolver->invalidate($tenant->id);
    }
}
