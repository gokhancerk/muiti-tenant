<?php

use App\Models\Tenant;
use App\Services\TenantManager;

describe('TenantIdentificationMiddleware', function () {

    beforeEach(function () {
        // Test tenant oluştur
        $this->tenant = Tenant::create([
            'name' => 'Test Company',
            'domain' => 'test.example.com',
            'is_active' => true,
        ]);
    });

    it('X-Tenant-ID header yoksa 400 Bad Request döner', function () {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Access Denied: X-Tenant-ID header missing'
            ]);
    });

    it('X-Tenant-ID header boş ise 400 Bad Request döner', function () {
        $response = $this->getJson('/api/projects', [
            'X-Tenant-ID' => ''
        ]);

        $response->assertStatus(400);
    });

    it('geçerli X-Tenant-ID ile istek başarılı olur', function () {
        $response = $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenant->id
        ]);

        $response->assertStatus(200);
    });

    it('TenantManager container\'dan doğru tenant ID ile resolve edilir', function () {
        $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenant->id
        ]);

        $manager = app(TenantManager::class);
        
        expect($manager->hasTenant())->toBeTrue();
        expect($manager->getTenantId())->toBe($this->tenant->id);
    });

    it('ardışık isteklerde tenant state sıfırlanır (scoped binding)', function () {
        // İlk istek - Tenant 1
        $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenant->id
        ]);

        // Yeni bir TenantManager instance alındığında (yeni istek simülasyonu)
        // scoped binding sayesinde her istekte yeni instance oluşur
        $this->app->forgetScopedInstances();
        
        $freshManager = app(TenantManager::class);
        expect($freshManager->hasTenant())->toBeFalse();
    });

});
