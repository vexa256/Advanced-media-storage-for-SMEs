<?php namespace App\Http\Requests;

use Common\Core\BaseFormRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ModifyAlbums extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $album = $this->route('album');
        $artistId = $this->request->get('artist_id', 0);

        $rules = [
            'name' => [
                'required', 'string', 'min:1', 'max:255',
                Rule::unique('albums')->where(function(Builder $query) use($artistId) {
                    return $query->where('artist_id', $artistId)
                        ->where('artist_type', $this->request->get('artist_type'));
                })->ignore($album ? $album->id : null)
            ],
            'artist_id'          => 'required|integer',
            'spotify_popularity' => 'integer|min:1|max:100|nullable',
            'release_date'       => 'date_format:Y-m-d|nullable',
            'image'              => 'nullable', //TODO url validation does not work on PHP 7.3 and Laravel 5.4,
            'tracks.*.name' => 'required|string|min:3|max:190',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.unique' => __('Artist already has album with this name.'),
        ];
    }
}
