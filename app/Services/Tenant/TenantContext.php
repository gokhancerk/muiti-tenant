<?php

namespace App\Services\Tenant;

/**
 * Tenant Context Data Transfer Object
 * 
 * İmmutable bir yapı olarak tenant metadata'sını taşır.
 * Middleware → TenantManager → Rate Limiter arasında contract görevi görür.
 * 
 * Bu DTO sayesinde:
 * - Source of truth değişse bile (DB → Cache) consumer'lar etkilenmez
 * - Tenant verisi tek bir yerde normalize edilir
 * - Type safety sağlanır
 */
readonly class TenantContext
{
    public function __construct(
        public int $id,
        public string $tier,
        public bool $isActive,
    ) {}

    /**
     * Eloquent Model'den DTO'ya dönüşüm
     */
    public static function fromModel(\App\Models\Tenant $tenant): self
    {
        return new self(
            id: $tenant->id,
            tier: $tenant->tier ?? 'free',
            isActive: (bool) $tenant->is_active,
        );
    }

    /**
     * Cache'den gelen array'den DTO'ya dönüşüm
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            tier: $data['tier'] ?? 'free',
            isActive: $data['is_active'] ?? false,
        );
    }

    /**
     * Cache'e yazılabilir array formatı
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tier' => $this->tier,
            'is_active' => $this->isActive,
        ];
    }
}
