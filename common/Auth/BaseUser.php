<?php namespace Common\Auth;

use App\User;
use Carbon\Carbon;
use Common\Auth\Permissions\Permission;
use Common\Auth\Permissions\Traits\HasPermissionsRelation;
use Common\Auth\Roles\Role;
use Common\Billing\Billable;
use Common\Billing\BillingPlan;
use Common\Files\FileEntry;
use Common\Files\FileEntryPivot;
use Common\Files\Traits\SetsAvailableSpaceAttribute;
use Common\Notifications\NotificationSubscription;
use Common\Settings\Settings;
use Eloquent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Arr;
use Storage;

/**
 * @property int $id
 * @property string|null $username
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $gender
 * @property-read Collection|Permission[] $permissions
 * @property string $email
 * @property string $password
 * @property integer|null $available_space
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $stripe_active
 * @property string|null $stripe_id
 * @property string|null $stripe_subscription
 * @property string|null $stripe_plan
 * @property string|null $last_four
 * @property string|null $trial_ends_at
 * @property string|null $subscription_ends_at
 * @property string $avatar
 * @property-read string $display_name
 * @property-read mixed $followers_count
 * @property-read bool $has_password
 * @property-read Collection|Role[] $roles
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read NotificationSubscription[]|Collection $notificationSubscriptions
 * @method BaseUser compact()
 * @method Builder whereNeedsNotificationFor(string $eventId)
 * @mixin Eloquent
 */
abstract class BaseUser extends Authenticatable
{
    use Notifiable, Billable, SetsAvailableSpaceAttribute, HasPermissionsRelation;

    // prevent avatar from being set along with other user details
    protected $guarded = ['id', 'avatar'];
    protected $hidden = ['password', 'remember_token', 'pivot', 'legacy_permissions', 'api_token'];
    protected $casts = [
        'id' => 'integer',
        'available_space' => 'integer',
        'email_verified_at' => 'datetime',
    ];
    protected $appends = ['display_name', 'has_password'];
    protected $billingEnabled = true;
    protected $gravatarSize;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->billingEnabled = app(Settings::class)->get('billing.enable');
    }

    /**
     * @return BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * @return string
     */
    public function routeNotificationForSlack()
    {
        return config('services.slack.webhook_url');
    }

    /**
     * @param Builder $query
     * @param string $notifId
     * @return Builder
     */
    public function scopeWhereNeedsNotificationFor(Builder $query, $notifId)
    {
        return $query->whereHas('notificationSubscriptions', function(Builder $builder) use ($notifId) {
            if (\Str::contains($notifId, '*')) {
                return $builder->where('notif_id', 'like', str_replace('*', '%', $notifId));
            } else {
                return $builder->where('notif_id', $notifId);
            }
        });
    }

    /**
     * @return HasMany
     */
    public function notificationSubscriptions() {
        return $this->hasMany(NotificationSubscription::class);
    }

    /**
     * @param array $options
     * @return BelongsToMany
     */
    public function entries($options = ['owner' => true])
    {
        $query = $this->morphToMany(FileEntry::class, 'model', 'file_entry_models', 'model_id', 'file_entry_id')
            ->using(FileEntryPivot::class)
            ->withPivot('owner', 'permissions');

        if (Arr::get($options, 'owner')) {
            $query->wherePivot('owner', true);
        }

        return $query->withTimestamps()->orderBy('file_entry_models.created_at', 'asc');
    }

    /**
     * Social profiles this users account is connected to.
     *
     * @return HasMany
     */
    public function social_profiles()
    {
        return $this->hasMany(SocialProfile::class);
    }

    /**
     * Get user avatar.
     *
     * @return string
     */
    public function getAvatarAttribute()
    {
        $value = $this->attributes['avatar'];

        // absolute link
        if ($value && \Str::contains($value, '//')) {
            // change google/twitter avatar imported via social login size
            $value = str_replace('.jpg?sz=50', ".jpg?sz=$this->gravatarSize", $value);
            if ($this->gravatarSize > 50) {
                // twitter
                $value = str_replace('_normal.jpg', '.jpg', $value);
            }
            return $value;
        }

        // relative link (for new and legacy urls)
        if ($value) {
            return Storage::disk('public')->url($value);
        }

        // gravatar
        $hash = md5(trim(strtolower($this->email)));

        return "https://www.gravatar.com/avatar/$hash?s={$this->gravatarSize}&d=retro";
    }

    /**
     * @param number $size
     * @return BaseUser
     */
    public function setGravatarSize($size)
    {
        $this->gravatarSize = $size;
        return $this;
    }

    /**
     * Compile user display name from available attributes.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        if ($this->username) {
            return $this->username;
        } else if ($this->first_name && $this->last_name) {
            return $this->first_name.' '.$this->last_name;
        } else if ($this->first_name) {
            return $this->first_name;
        } else if ($this->last_name) {
            return $this->last_name;
        } else {
            return explode('@', $this->email)[0];
        }
    }

    /**
     * Check if user has a password set.
     *
     * @return bool
     */
    public function getHasPasswordAttribute()
    {
        return isset($this->attributes['password']) && $this->attributes['password'];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasPermission($name)
    {
        return !is_null($this->getPermission($name)) || !is_null($this->getPermission('admin'));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasExactPermission($name)
    {
        return !is_null($this->getPermission($name));
    }

    /**
     * @return self
     */
    public function loadPermissions()
    {
        if ($this->relationLoaded('permissions')) {
            return $this;
        }

        $query = app(Permission::class)
            ->join('permissionables', 'permissions.id', 'permissionables.permission_id')
            ->where(['permissionable_id' => $this->id, 'permissionable_type' => User::class]);

        if ($this->roles->pluck('id')->isNotEmpty()) {
            $query->orWhere(function(Builder $builder) {
                return $builder->whereIn('permissionable_id', $this->roles->pluck('id'))
                    ->where('permissionable_type', Role::class);
            });
        }

        if ($plan = $this->getBillingPlan()) {
            $query->orWhere(function(Builder $builder) use($plan) {
                return $builder->where('permissionable_id', $plan->id)
                    ->where('permissionable_type', BillingPlan::class);
            });
        }

        $permissions = $query->select(['permissions.id', 'name', 'permissionables.restrictions', 'permissionable_type'])
            ->get()
            ->sortBy(function($value) {
                if ($value['permissionable_type'] === User::class) {
                    return 1;
                } else if ($value['permissionable_type'] === BillingPlan::class) {
                    return 2;
                } else {
                    return 3;
                }
            })
            ->groupBy('id')

            // merge restrictions from all permissions
            ->map(function(Collection $group) {
                return $group->reduce(function(Permission $carry, Permission $permission) {
                    return $carry->mergeRestrictions($permission);
                }, $group[0]);
            });

        $this->setRelation('permissions', $permissions->values());

        return $this;
    }

    /**
     * @param string $name
     * @return Permission
     */
    public function getPermission($name)
    {
        $this->loadPermissions();
        return $this->permissions->first(function(Permission $permission) use($name) {
            return $permission->name === $name;
        });
    }

    /**
     * @return BillingPlan
     */
    public function getBillingPlan()
    {
        if ( ! $this->billingEnabled) return null;

        if ($subscription = $this->subscriptions->first()) {
            return $subscription->plan;
        } else {
            return BillingPlan::where('free', true)->first();
        }
    }

    /**
     * @param string $permissionName
     * @param string $restriction
     * @return int|null
     */
    public function getRestrictionValue($permissionName, $restriction)
    {
        $permission = $this->getPermission($permissionName);
        return $permission ? $permission->getRestrictionValue($restriction) : null;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompact(Builder $query)
    {
        return $query->select('users.id', 'users.avatar', 'users.email', 'users.first_name', 'users.last_name', 'users.username');
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        ResetPassword::$createUrlCallback = function($user, $token) {
            return url("password/reset/$token");
        };
        $this->notify(new ResetPassword($token));
    }

    /**
     * @return self
     */
    public function findAdmin()
    {
        return $this->whereHas('permissions', function(Builder $query) {
            $query->where('name', 'admin');
        })->first();
    }
}
