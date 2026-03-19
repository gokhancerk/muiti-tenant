<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Tenant-Aware Rate Limiting Tests
 * 
 * Bu testler tenant bazlı rate limiting'in doğru çalıştığını doğrular.
 * Ana hedef: Bir tenant limit aşınca diğer tenant etkilenmemeli (noisy-neighbor protection).
 * 
 * NOT: Test ortamında düşük limitler kullanıyoruz (gerçek HTTP istekleriyle test)
 */
describe('Tenant-Aware Rate Limiting', function () {

    /**
     * Test 1: Tenant Isolation (Noisy-Neighbor Protection)
     * Tenant A limit aşınca Tenant B aynı endpoint'e erişebilmeli.
     */
    it('tenant A limit aşınca tenant B etkilenmez (noisy-neighbor protection)', function () {
        // İki tenant oluştur (free tier: 60 req/min)
        $tenantA = Tenant::create(['name' => 'Tenant A', 'tier' => 'free', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'tier' => 'free', 'is_active' => true]);

        // Tenant A için 60 istek yap (limiti doldur)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $tenantA->id])->getJson('/api/projects');
        }

        // 61. istek: Tenant A artık 429 almalı
        $responseA = $this->withHeaders(['X-Tenant-ID' => $tenantA->id])->getJson('/api/projects');
        $responseA->assertStatus(429)
            ->assertJson([
                'error' => 'Too Many Requests',
            ]);

        // Tenant B hala erişebilmeli (kendi limiti dolmadı)
        $responseB = $this->withHeaders(['X-Tenant-ID' => $tenantB->id])->getJson('/api/projects');
        $responseB->assertStatus(200);
    });

    /**
     * Test 2: Tier-Based Limits
     * Free ve Pro tenant'lar farklı limitler almalı.
     */
    it('free tenant ve pro tenant farklı limit alır', function () {
        $freeTenant = Tenant::create(['name' => 'Free Tenant', 'tier' => 'free', 'is_active' => true]);
        $proTenant = Tenant::create(['name' => 'Pro Tenant', 'tier' => 'pro', 'is_active' => true]);

        // Free tenant için 60 istek yap (limit: 60 req/min)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $freeTenant->id])->getJson('/api/projects');
        }

        // Free tenant 429 almalı (61. istek)
        $freeResponse = $this->withHeaders(['X-Tenant-ID' => $freeTenant->id])->getJson('/api/projects');
        $freeResponse->assertStatus(429);

        // Pro tenant için 60 istek yap (limit: 300 req/min, hala müsait)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $proTenant->id])->getJson('/api/projects');
        }

        // Pro tenant hala erişebilmeli (60 < 300)
        $proResponse = $this->withHeaders(['X-Tenant-ID' => $proTenant->id])->getJson('/api/projects');
        $proResponse->assertStatus(200);
    });

    /**
     * Test 3: Route-Based Policy
     * Aynı tenant için farklı route'larda farklı policy çalışmalı.
     */
    it('aynı tenant için read ve write endpoint farklı limite sahip', function () {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'tier' => 'free', 'is_active' => true]);

        // Write endpoint için 20 istek yap (limit: 20 req/min for free)
        for ($i = 0; $i < 20; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $tenant->id])->postJson('/api/projects', []);
        }

        // Write endpoint 429 döner (21. istek)
        $writeResponse = $this->withHeaders(['X-Tenant-ID' => $tenant->id])->postJson('/api/projects', []);
        $writeResponse->assertStatus(429);

        // Read endpoint hala erişilebilir (farklı policy, kendi limiti dolmadı)
        $readResponse = $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        $readResponse->assertStatus(200);
    });

    /**
     * Test 4: Fail-Fast Without Tenant Context
     * Tenant context yoksa sistem hızlıca hata döner.
     */
    it('tenant context yoksa fail-fast davranışı korunur', function () {
        // X-Tenant-ID header olmadan istek
        $response = $this->getJson('/api/projects');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Access Denied: X-Tenant-ID header missing'
            ]);
    });

    /**
     * Test 5: Rate Limit Window Reset
     * Limit penceresi dolunca erişim geri gelmeli.
     */
    it('rate limit penceresi sıfırlanınca erişim geri gelir', function () {
        $tenant = Tenant::create(['name' => 'Reset Test Tenant', 'tier' => 'free', 'is_active' => true]);

        // Limiti doldur (60 istek)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        }

        // 429 aldığını doğrula
        $blockedResponse = $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        $blockedResponse->assertStatus(429);

        // Tüm cache'i temizle (pencere reset simülasyonu)
        // Laravel rate limiter internal key formatı kullandığı için Cache::flush() kullanıyoruz
        Cache::flush();

        // Erişim geri gelmeli
        $allowedResponse = $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        $allowedResponse->assertStatus(200);
    });

    /**
     * Bonus Test: 429 Response Headers
     * Retry-After header'ının döndüğünü doğrula.
     */
    it('429 response Retry-After header içerir', function () {
        $tenant = Tenant::create(['name' => 'Header Test Tenant', 'tier' => 'free', 'is_active' => true]);

        // Limiti doldur (60 istek)
        for ($i = 0; $i < 60; $i++) {
            $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        }

        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->id])->getJson('/api/projects');
        
        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    });

});
