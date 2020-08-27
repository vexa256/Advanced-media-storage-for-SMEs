<?php namespace Common\Pages;

use Common\Core\BaseController;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CustomPageController extends BaseController
{
    /**
     * @var CustomPage
     */
    private $page;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param CustomPage $page
     * @param Request $request
     */
    public function __construct(CustomPage $page, Request $request)
    {
        $this->page = $page;
        $this->request = $request;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $userId = $this->request->get('userId');
        $this->authorize('index', [CustomPage::class, $userId]);

        $paginator = new Paginator($this->page, $this->request->all());
        $paginator->with('user');
        if ($type = $this->request->get('type')) {
            $paginator->where('type', $type);
        }

        if ($userId) {
            $paginator->where('user_id', $userId);
        }

        $paginator->searchCallback = function(Builder $query, $term) {
            $query->where('slug', 'LIKE', "%$term%");
            $query->orWhere('body', 'LIKE', "$term%");
        };

        $pagination = $paginator->paginate();

        $pagination->transform(function($page) {
            $page->body = Str::limit(strip_tags($page->body), 200);
            return $page;
        });

        return $this->success(['pagination' => $pagination]);
    }

    /**
     * @param CustomPage $page
     * @return JsonResponse
     */
    public function show(CustomPage $page)
    {
        $this->authorize('show', $page);

        return $this->success(['page' => $page]);
    }

    /**
     * @param CrupdatePageRequest $request
     * @return Response
     */
    public function store(CrupdatePageRequest $request)
    {
        $this->authorize('store', CustomPage::class);

        $page = app(CrupdatePage::class)->execute(
            $request->all()
        );

        return $this->success(['page' => $page]);
    }

    /**
     * @param CustomPage $page
     * @param CrupdatePageRequest $request
     * @return JsonResponse
     */
    public function update(CustomPage $page, CrupdatePageRequest $request)
    {
        $this->authorize('update', $page);

        $page = app(CrupdatePage::class)->execute(
            $request->all(),
            $page
        );

        return $this->success(['page' => $page]);
    }

    /**
     * @param string $ids
     * @return Response
     */
    public function destroy($ids)
    {
        $pageIds = explode(',', $ids);
        $this->authorize('destroy', [CustomPage::class, $pageIds]);

        $this->page->whereIn('id', $pageIds)->delete();

        return $this->success();
    }
}
