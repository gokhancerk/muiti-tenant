<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
   protected $fillable = ['tenant_id', 'name', 'description'];

   protected  static function booted() : void
   {
    // Tüm Project sorguları istisnasız bu filtreden geçecek
    static::addGlobalScope(new TenantScope());

    // Veri oluşturulurken tenant_id'yi otomatik atama

    static::creating(function ($project) {
        $tenantManager = app(TenantManager::class);
        if ($tenantManager->hasTenant()) {
            $project->tenant_id = $tenantManager->getTenantId();
        }
    });

   }
 
}
