<?php

use App\Services\TenantManager;

describe('TenantManager', function () {

    it('başlangıçta tenant ID olmadan oluşturulur', function () {
        $manager = new TenantManager();
        
        expect($manager->hasTenant())->toBeFalse();
    });

    it('tenant ID set edilebilir', function () {
        $manager = new TenantManager();
        $manager->setTenantId(42);
        
        expect($manager->hasTenant())->toBeTrue();
        expect($manager->getTenantId())->toBe(42);
    });

    it('tenant ID olmadan getTenantId çağrılınca RuntimeException fırlatır', function () {
        $manager = new TenantManager();
        
        $manager->getTenantId();
    })->throws(RuntimeException::class, 'Tenant ID NOT Found');

    it('birden fazla kez set edildiğinde son değeri döner', function () {
        $manager = new TenantManager();
        
        $manager->setTenantId(1);
        $manager->setTenantId(99);
        
        expect($manager->getTenantId())->toBe(99);
    });

});
