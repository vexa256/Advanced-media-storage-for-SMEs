<?php namespace Common\Auth\Roles;

use Common\Auth\Permissions\Permission;
use App\User;
use Carbon\Carbon;
use Common\Auth\Permissions\Traits\HasPermissionsRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property integer $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property boolean $default
 * @property-read Collection|User[] $users
 * @property-read Collection|Permission[] $permissions
 * @mixin Eloquent
 * @property int $guests
 */
class Role extends Model
{
    use HasPermissionsRelation;

    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @var array
     */
    protected $hidden = ['pivot', 'legacy_permissions'];

    /**
     * @var array
     */
    protected $casts = ['id' => 'integer', 'default' => 'boolean', 'guests' => 'boolean'];

    /**
     * Get default role for assigning to new users.
     *
     * @return Role|null
     */
    public function getDefaultRole()
    {
        return $this->where('default', 1)->first();
    }

    /**
     * Users belonging to this role.
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role');
    }
}
