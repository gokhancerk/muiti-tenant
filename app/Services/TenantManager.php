<?php 

namespace App\Services;

use App\Services\Tenant\TenantContext;
use RuntimeException;

/**
 * Bu sınıf uygulamanın bellek içi (in-memory) veri taşıyıcısıdır. 
 * Hata yönetimi (error handling) katıdır; eğer tenant set edilmeden veri istenirse 
 * sistem sessizce kalmaz, doğrudan exception fırlatır.
 * 
 * TenantContext DTO ile çalışır - source of truth değişse bile contract bozulmaz.
 */
class TenantManager
{
    private ?TenantContext $context = null;

    /**
     * Tenant context'i set et
     * TenantResolver'dan gelen DTO burada saklanır.
     */
    public function setContext(TenantContext $context): void
    {
        $this->context = $context;
    }

    /**
     * @deprecated Use setContext() instead
     * Geriye uyumluluk için korundu
     */
    public function setTenantId(int $tenantId, string $tier): void
    {
        $this->context = new TenantContext(
            id: $tenantId,
            tier: $tier,
            isActive: true
        );
    }

    public function getTenantId(): int
    {
        if ($this->context === null) {
            throw new RuntimeException("Tenant ID NOT Found");
        }
        return $this->context->id;
    }

    public function getTier(): string
    {
        return $this->context?->tier ?? 'free';
    }

    public function hasTenant(): bool
    {
        return $this->context !== null;
    }

    /**
     * Tam TenantContext DTO'sunu döndür
     */
    public function getContext(): ?TenantContext
    {
        return $this->context;
    }
}
