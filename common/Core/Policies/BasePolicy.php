<?php

namespace Common\Core\Policies;

use App\User;
use Common\Core\Exceptions\AccessResponseWithAction;
use Common\Settings\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;
use Str;

const UPGRADE_ACTION = ['label' => 'Upgrade', 'action' => '/billing/upgrade'];

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    public function __construct(Request $request, Settings $settings)
    {
        $this->request = $request;
        $this->settings = $settings;
    }

    protected function denyWithAction($message, array $action)
    {
        /** @var AccessResponseWithAction $response */
        $response = AccessResponseWithAction::deny($message, $action);
        $response->action = $action;
        return $response;
    }

    /**
     * @param User $user
     * @param string $namespace
     * @param string $relation
     * @return bool|AccessResponseWithAction
     */
    protected function storeWithCountRestriction(User $user, $namespace, $relation = null)
    {
        // "App\SomeModel" => "Some_Model"
        $resourceName = Str::snake(class_basename($namespace));

        // "Some_Model" => "some_models"
        $pluralName = strtolower(Str::plural($resourceName));

        // user can't create resource at all
        if ( ! $user->hasPermission("$pluralName.create")) {
            return false;
        }

        // user is admin, can ignore count restriction
        if ($user->hasPermission('admin')) {
            return true;
        }

        // user does not have any restriction on maximum link count
        $maxCount = $user->getRestrictionValue("$pluralName.create", 'count');

        if ( ! $maxCount) {
            return true;
        }

        // check if user did not go over their max quota
        $relation = $relation ?: $pluralName;
        if ($user->$relation->count() >= $maxCount) {
            $displayPlural = ucwords(str_replace('_', ' ', $pluralName));
            $displaySingular = Str::singular(ucwords(str_replace('_', ' ', $pluralName)));
            $message = __('policies.quota_exceeded', ['resources' => $displayPlural, 'resource' => $displaySingular]);
            return $this->denyWithAction($message, $this->settings->get('billing.enable') ? UPGRADE_ACTION : null);
        }

        return true;
    }
}
