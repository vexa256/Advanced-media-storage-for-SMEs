<?php namespace App\Http\Controllers;

use App;
use App\Genre;
use App\Services\Artists\NormalizesArtist;
use App\Track;
use Cache;
use Carbon\Carbon;
use Common\Core\BaseController;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\Providers\ProviderResolver;
use Common\Settings\Settings;
use Illuminate\Validation\Rule;

class GenreController extends BaseController
{
    use NormalizesArtist;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var ProviderResolver
     */
    private $resolver;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Settings $settings
     * @param ProviderResolver $resolver
     * @param Genre $genre
     * @param Request $request
     */
    public function __construct(
        Settings $settings,
        ProviderResolver $resolver,
        Genre $genre,
        Request $request
    )
    {
        $this->settings = $settings;
        $this->resolver = $resolver;
        $this->genre = $genre;
        $this->request = $request;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $this->authorize('index', Genre::class);

        $pagination = (new Paginator($this->genre, $this->request->all()))
            ->paginate();

        return $this->success(['pagination' => $pagination]);
    }

    /**
     * @param string $name
     * @return JsonResponse
     */
    public function show($name)
    {
        $this->authorize('index', Genre::class);

        $genre = $this->genre->where('name', str_replace('-', ' ', $name))
            ->orWhere('name', $name)
            ->firstOrFail();

        // only load artists from 3rd party site for first pagination page
        if ( ! $this->request->get('page')) {
            Cache::remember("genres.$name.artists", Carbon::now()->addDays(7), function() use ($genre) {
                $this->resolver->get('genreArtists')->getContent($genre);
                return 'noop';
            });
        }

        if (app(Settings::class)->get('player.artist_type') === 'artist') {
            $artists = $genre->artists()->groupBy('name');
            if ($query = $this->request->get('query')) {
                $artists->where('name', 'like', $query.'%');
            }
            $pagination = $artists->paginate(20);
            $pagination->transform(function($artist) {
                return $this->normalizeArtist($artist);
            });
        } else {
           $tracks = app(Track::class)->whereHas('genres', function(Builder $builder) use($genre) {
               return $builder->where('genres.name', $genre->name);
           })->limit(50)->get();
           $tracks->load('artists');
           $artists = $tracks->pluck('artists')->flatten(1)->unique('id')->values();
           $pagination = new LengthAwarePaginator($artists, $artists->count(), 20);
        }

        return $this->success(['genre' => $genre, 'artists' => $pagination]);
    }

    /**
     * @return JsonResponse
     */
    public function store()
    {
        $this->authorize('store', Genre::class);

        $this->validate($this->request, [
            'name' => 'required|unique:genres',
            'image' => 'string',
            'popularity' => 'nullable|integer|min:1|max:100'
        ]);

        $newGenre = $this->genre->create([
            'name' => slugify($this->request->get('name')),
            'display_name' => $this->request->get('display_name') ?: $this->request->get('name'),
            'image' => $this->request->get('image'),
            'popularity' => $this->request->get('popularity'),
        ]);

        return $this->success(['genre' => $newGenre]);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $this->authorize('update', Genre::class);

        $this->validate($this->request, [
            'name' => Rule::unique('genres')->ignore($id),
            'image' => 'string',
            'popularity' => 'nullable|integer|min:1|max:100'
        ]);

        $data = $this->request->all();
        if ($data['name']) {
            $data['name'] = slugify($data['name']);
        }

        $genre = $this->genre
            ->find($id)
            ->update($data);

        return $this->success(['genre' => $genre]);
    }

    /**
     * @return JsonResponse
     */
    public function destroy()
    {
        $this->authorize('destroy', Genre::class);

        $this->validate($this->request, [
            'ids' => 'required|array|exists:genres,id'
        ]);

        $count = $this->genre->destroy($this->request->get('ids'));

        return $this->success(['count' => $count]);
    }
}
