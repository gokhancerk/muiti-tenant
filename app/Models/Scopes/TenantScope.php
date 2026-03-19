<?php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\TenantManager;

/**
 * undocumented class
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model) : void
    {
        // Container'dan tenant state'ini çözüyoruz
        $tenantManager = app(TenantManager::class);

        // Eğer sistemde aktif bir tenant varsa, sorguyu zorla kısıtla
        if ($tenantManager->hasTenant()) {
            $builder->where('tenant_id', $tenantManager->getTenantId());
        }

    }
}
