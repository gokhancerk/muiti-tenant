<?php 

namespace App\Services;

use RuntimeException;

/**
 * Bu sınıf uygulamanın bellek içi (in-memory) veri taşıyıcısıdır. Hata yönetimi (error handling) katıdır; eğer tenant set edilmeden veri istenirse sistem sessizce kalmaz, doğrudan exception fırlatır.
 */
class TenantManager
{
    private ?int $tenantId = null;

    public function setTenantId(int $tenantId) : void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId() : int
    {
        if ($this->tenantId === null) {
            throw new RuntimeException("Tenant ID NOT Found");
        }
        return $this->tenantId;
    }

    public function hasTenant() : bool
    {
        return $this->tenantId !== null;
    }

}
