<?php namespace Common\Files\Actions;

use Auth;
use App\User;
use Common\Billing\BillingPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Common\Settings\Settings;

class GetUserSpaceUsage {

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
        $this->user = Auth::user();
        $this->settings = $settings;
    }

    /**
     * Get disk space that current user is currently using.
     *
     * @return array
     */
    public function execute() {
        return [
            'used' => $this->getSpaceUsed(),
            'available' => $this->getAvailableSpace(),
        ];
    }

    /**
     * Space current user is using in bytes.
     *
     * @return int
     */
    private function getSpaceUsed()
    {
        return (int) $this->user
            ->entries(['owner' => true])
            ->where(function(Builder $builder) {
                // only count size of folders (they will include all children size sumed already)
                // and files that don't have any parent folder (uploaded at root)
                $builder->whereNull('parent_id')
                    ->orWhere('type', 'folder');
            })
            ->withTrashed()
            ->sum('file_size');
    }

    /**
     * Maximum available space for current user in bytes.
     *
     * @return int
     */
    public function getAvailableSpace() {

        $space = null;

        if ( ! is_null($this->user->available_space)) {
            $space = $this->user->available_space;
        } else if (app(Settings::class)->get('billing.enable')) {
            if ($this->user->subscribed()) {
                $space = $this->user->subscriptions->first()->mainPlan()->available_space;
            } else if ($freePlan = BillingPlan::where('free', true)->first()) {
                $space = $freePlan->available_space;
            }
        }

        // space is not set at all on user or billing plans
        if (is_null($space)) {
            $defaultSpace = $this->settings->get('uploads.available_space');
            return is_numeric($defaultSpace) ? abs($defaultSpace) : null;
        } else {
            return abs($space);
        }
    }

    /**
     * Return if user has used up his disk space.
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function userIsOutOfSpace(UploadedFile $file) {
        $availableSpace = $this->getAvailableSpace();
        // unlimited space
        if (is_null($availableSpace)) {
            return false;
        }
        return ($this->getSpaceUsed() + $file->getSize()) > $availableSpace;
    }
}
