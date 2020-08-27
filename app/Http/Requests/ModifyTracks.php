<?php namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Common\Core\BaseFormRequest;

class ModifyTracks extends BaseFormRequest
{
    public function messages()
    {
        return [
            'artists.required_without' => [
                __('Could not automatically determine track artist. Select artist manually.'),
            ]
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $trackId = $this->route('id');

        $name = ['required', 'string', 'min:1', 'max:255'];

        if ($this->request->get('album_id')) {
            $name[] = Rule::unique('tracks')->where(function(Builder $query) {
                $query->where('album_id', $this->request->get('album_id'));
            })->ignore($trackId);
        }

        $rules = [
            'name' => $name,
            'number'             => 'required_with:album_id|min:1',
            'album_name'         => 'required_with:album_id|min:1|max:255',
            'duration'           => 'required|integer|min:1',
            'artists'            => 'required_without:user_id',
            'spotify_popularity' => 'min:1|max:100|nullable',
            'album_id'           => 'integer|min:1|exists:albums,id',
        ];

        return $rules;
    }
}
