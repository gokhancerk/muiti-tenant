<?php

use App\Models\Project;
use App\Models\Tenant;
use App\Services\TenantManager;

describe('TenantScope - Veri İzolasyonu', function () {

    beforeEach(function () {
        // İki farklı tenant oluştur
        $this->tenantA = Tenant::create([
            'name' => 'Company A',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Company B', 
            'is_active' => true,
        ]);

        // Her tenant için projeler oluştur
        Project::withoutGlobalScopes()->insert([
            ['tenant_id' => $this->tenantA->id, 'name' => 'Project A1', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenantA->id, 'name' => 'Project A2', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenantB->id, 'name' => 'Project B1', 'created_at' => now(), 'updated_at' => now()],
        ]);
    });

    it('Tenant A sadece kendi projelerini görür', function () {
        $response = $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenantA->id
        ]);

        $response->assertStatus(200);
        
        $projects = $response->json();
        
        expect($projects)->toHaveCount(2);
        expect(collect($projects)->pluck('name')->all())
            ->toContain('Project A1', 'Project A2')
            ->not->toContain('Project B1');
    });

    it('Tenant B sadece kendi projelerini görür', function () {
        $response = $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenantB->id
        ]);

        $response->assertStatus(200);
        
        $projects = $response->json();
        
        expect($projects)->toHaveCount(1);
        expect($projects[0]['name'])->toBe('Project B1');
    });

    it('çapraz tenant veri sızıntısı (cross-tenant leak) mümkün değildir', function () {
        // Tenant A olarak giriş yap
        $responseA = $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenantA->id
        ]);

        // Tenant B olarak giriş yap
        $responseB = $this->getJson('/api/projects', [
            'X-Tenant-ID' => $this->tenantB->id
        ]);

        $projectsA = collect($responseA->json())->pluck('tenant_id')->unique();
        $projectsB = collect($responseB->json())->pluck('tenant_id')->unique();

        // Her tenant sadece kendi ID'sine sahip verileri görmeli
        expect($projectsA->all())->toBe([$this->tenantA->id]);
        expect($projectsB->all())->toBe([$this->tenantB->id]);
    });

});

describe('TenantScope - Otomatik Tenant ID Ataması', function () {

    beforeEach(function () {
        $this->tenant = Tenant::create([
            'name' => 'Auto Assign Test',
            'is_active' => true,
        ]);
    });

    it('yeni proje oluştururken tenant_id otomatik atanır', function () {
        $response = $this->postJson('/api/projects', [
            'name' => 'Auto Created Project',
            'description' => 'Test description',
        ], [
            'X-Tenant-ID' => $this->tenant->id
        ]);

        $response->assertStatus(201);
        
        $project = $response->json();
        
        expect($project['tenant_id'])->toBe($this->tenant->id);
        expect($project['name'])->toBe('Auto Created Project');
    });

    it('request body\'de tenant_id gönderilmese bile doğru tenant atanır', function () {
        $response = $this->postJson('/api/projects', [
            'name' => 'No Tenant ID in Body',
        ], [
            'X-Tenant-ID' => $this->tenant->id
        ]);

        $response->assertStatus(201);
        
        $savedProject = Project::withoutGlobalScopes()
            ->where('name', 'No Tenant ID in Body')
            ->first();
        
        expect($savedProject->tenant_id)->toBe($this->tenant->id);
    });

});

describe('TenantScope - withoutGlobalScopes', function () {

    beforeEach(function () {
        $this->tenantA = Tenant::create(['name' => 'A', 'is_active' => true]);
        $this->tenantB = Tenant::create(['name' => 'B', 'is_active' => true]);

        Project::withoutGlobalScopes()->insert([
            ['tenant_id' => $this->tenantA->id, 'name' => 'PA', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $this->tenantB->id, 'name' => 'PB', 'created_at' => now(), 'updated_at' => now()],
        ]);
    });

    it('withoutGlobalScopes ile tüm projeler erişilebilir (admin senaryosu)', function () {
        // TenantManager'a bir tenant set et
        app(TenantManager::class)->setTenantId($this->tenantA->id);

        // Normal sorgu - sadece Tenant A projeleri
        $filtered = Project::all();
        expect($filtered)->toHaveCount(1);

        // Global scope bypass - tüm projeler
        $all = Project::withoutGlobalScopes()->get();
        expect($all)->toHaveCount(2);
    });

});
