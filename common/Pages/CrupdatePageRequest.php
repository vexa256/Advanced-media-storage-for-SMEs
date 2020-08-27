<?php

namespace Common\Pages;

use Auth;
use Common\Core\BaseFormRequest;
use Illuminate\Validation\Rule;

class CrupdatePageRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        $required = $this->getMethod() === 'POST' ? 'required' : '';
        $ignore = $this->getMethod() === 'PUT' ? $this->route('page')->id : '';
        $userId = $this->route('page') ? $this->route('page')->user_id : Auth::id();

        return [
            'title' => [
                $required, 'string', 'min:3', 'max:250',
                $this->uniqueRule($userId, $ignore),
            ],
            'slug' => [
                'nullable', 'string', 'min:3', 'max:250',
                $this->uniqueRule($userId, $ignore),
            ],
            'body' => "required|string|min:1",
        ];
    }

    private function uniqueRule($userId, $ignore)
    {
        $rule = Rule::unique('custom_pages');

        // https://github.com/laravel/framework/issues/25374
        if ($userId) {
            $rule->where('user_id', $userId);
        } else {
            $rule->whereNull('user_id');
        }

        return $rule->ignore($ignore);
    }
}
