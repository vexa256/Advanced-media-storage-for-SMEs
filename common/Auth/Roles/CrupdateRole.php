<?php

namespace Common\Auth\Roles;

use Common\Auth\Permissions\Traits\SyncsPermissions;
use Illuminate\Support\Arr;

class CrupdateRole
{
    use SyncsPermissions;

    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * @param array $data
     * @param Role $role
     * @return Role
     */
    public function execute($data, $role = null)
    {
        if ( ! $role) {
            $role = $this->role->newInstance([]);
        }

        $attributes = [
            'name' => Arr::get($data, 'name'),
            'default' => Arr::get($data, 'default') ?: false,
            'guests' => Arr::get($data, 'guests') ?: false,
        ];

        $role->fill($attributes)->save();

        // always sync permissions, detach all if "null" is given as permissions
        $this->syncPermissions($role, Arr::get($data, 'permissions', []));

        return $role;
    }
}
