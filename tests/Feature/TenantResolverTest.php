<?php

use App\Models\Tenant;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantResolver;
use Illuminate\Support\Facades\Cache;

/**
 * TenantResolver Unit Tests
 * 
 * Cache-aware tenant resolution testleri.
 * Read-through cache pattern'in doğru çalıştığını doğrular.
 */
describe('TenantResolver', function () {

    beforeEach(function () {
        // Her test öncesi cache'i temizle
        Cache::flush();
    });

    /**
     * Test: İlk çağrıda DB'den okur ve cache'e yazar
     */
    it('ilk çağrıda DB\'den okur ve cache\'e yazar', function () {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'tier' => 'pro',
            'is_active' => true,
        ]);

        $resolver = app(TenantResolver::class);

        // İlk çağrı - DB'den okumalı ve cache'e yazmalı
        $context = $resolver->resolve($tenant->id);

        expect($context)->toBeInstanceOf(TenantContext::class);
        expect($context->id)->toBe($tenant->id);
        expect($context->tier)->toBe('pro');
        expect($context->isActive)->toBeTrue();

        // Cache'de olmalı
        $cached = Cache::get("tenant_context:{$tenant->id}");
        expect($cached)->not->toBeNull();
        expect($cached['id'])->toBe($tenant->id);
    });

    /**
     * Test: İkinci çağrıda cache'den okur (DB query yok)
     */
    it('ikinci çağrıda cache\'den okur', function () {
        $tenant = Tenant::create([
            'name' => 'Cached Tenant',
            'tier' => 'free',
            'is_active' => true,
        ]);

        $resolver = app(TenantResolver::class);

        // İlk çağrı - cache'e yazar
        $context1 = $resolver->resolve($tenant->id);

        // İkinci çağrı - cache'den okumalı
        $context2 = $resolver->resolve($tenant->id);

        // Her iki çağrı da aynı veriyi döndürmeli
        expect($context1->id)->toBe($context2->id);
        expect($context1->tier)->toBe($context2->tier);
        
        // Cache'de olduğunu doğrula
        expect(Cache::has("tenant_context:{$tenant->id}"))->toBeTrue();
    });

    /**
     * Test: Olmayan tenant için null döner
     */
    it('olmayan tenant için null döner', function () {
        $resolver = app(TenantResolver::class);

        $context = $resolver->resolve(999999);

        expect($context)->toBeNull();
    });

    /**
     * Test: Cache invalidation çalışır
     */
    it('invalidate() cache\'i temizler', function () {
        $tenant = Tenant::create([
            'name' => 'Invalidation Test',
            'tier' => 'pro',
            'is_active' => true,
        ]);

        $resolver = app(TenantResolver::class);

        // Cache'e yaz
        $resolver->resolve($tenant->id);
        expect(Cache::has("tenant_context:{$tenant->id}"))->toBeTrue();

        // Invalidate et
        $resolver->invalidate($tenant->id);

        // Cache boş olmalı
        expect(Cache::has("tenant_context:{$tenant->id}"))->toBeFalse();
    });

    /**
     * Test: Tenant update edilince cache invalidate olur (Observer)
     */
    it('tenant update edilince cache invalidate olur', function () {
        $tenant = Tenant::create([
            'name' => 'Observer Test',
            'tier' => 'free',
            'is_active' => true,
        ]);

        $resolver = app(TenantResolver::class);

        // Cache'e yaz
        $resolver->resolve($tenant->id);
        expect(Cache::has("tenant_context:{$tenant->id}"))->toBeTrue();

        // Tenant'ı güncelle (Observer tetiklenmeli)
        $tenant->tier = 'pro';
        $tenant->save();

        // Cache temizlenmiş olmalı
        expect(Cache::has("tenant_context:{$tenant->id}"))->toBeFalse();

        // Yeni resolve ettiğimizde güncel veri gelmeli
        $freshContext = $resolver->resolve($tenant->id);
        expect($freshContext->tier)->toBe('pro');
    });

});

describe('TenantContext DTO', function () {

    it('fromModel() doğru şekilde dönüşüm yapar', function () {
        $tenant = Tenant::create([
            'name' => 'DTO Test',
            'tier' => 'pro',
            'is_active' => true,
        ]);

        $context = TenantContext::fromModel($tenant);

        expect($context->id)->toBe($tenant->id);
        expect($context->tier)->toBe('pro');
        expect($context->isActive)->toBeTrue();
    });

    it('fromArray() doğru şekilde dönüşüm yapar', function () {
        $data = [
            'id' => 123,
            'tier' => 'free',
            'is_active' => false,
        ];

        $context = TenantContext::fromArray($data);

        expect($context->id)->toBe(123);
        expect($context->tier)->toBe('free');
        expect($context->isActive)->toBeFalse();
    });

    it('toArray() cache\'e yazılabilir format döner', function () {
        $context = new TenantContext(
            id: 456,
            tier: 'pro',
            isActive: true,
        );

        $array = $context->toArray();

        expect($array)->toBe([
            'id' => 456,
            'tier' => 'pro',
            'is_active' => true,
        ]);
    });

    it('tier null gelirse free varsayılır', function () {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->tier = null;
        $tenant->is_active = true;

        $context = TenantContext::fromModel($tenant);

        expect($context->tier)->toBe('free');
    });
});
