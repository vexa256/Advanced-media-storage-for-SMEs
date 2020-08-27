<?php

namespace Common\Auth\Permissions\Traits;


use Common\Auth\Permissions\Permission;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasPermissionsRelation
{
    /**
     * @return MorphToMany
     */
    public function permissions()
    {
        return $this->morphToMany(Permission::class, 'permissionable')
            ->withPivot('restrictions')
            ->select('name', 'permissions.id', 'permissions.restrictions');
    }
}