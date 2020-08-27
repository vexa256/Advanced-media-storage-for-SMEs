<?php

namespace App\Http\Controllers;

use App\Actions\Channel\LoadChannelContent;
use App\Services\Channel\UpdateChannelContent;
use Cache;
use Common\Core\BaseController;
use Common\Database\Paginator;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Channel;
use Illuminate\Http\Response;
use App\Http\Requests\CrupdateChannelRequest;
use App\Actions\Channel\CrupdateChannel;
use Illuminate\Support\Str;

class ChannelController extends BaseController
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Channel $channel
     * @param Request $request
     */
    public function __construct(Channel $channel, Request $request)
    {
        $this->channel = $channel;
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function index()
    {
        $userId = $this->request->get('userId');
        $this->authorize('index', [Channel::class, $userId]);

        $paginator = new Paginator($this->channel, $this->request->all());

        if ($userId = $paginator->param('userId')) {
            $paginator->where('user_id', $userId);
        }

        if ($channelIds = $paginator->param('channelIds')) {
            $paginator->query()->whereIn('id', explode(',', $channelIds));
        }

        $pagination = $paginator->paginate();

        return $this->success(['pagination' => $pagination]);
    }

    /**
     * @param Channel $channel
     * @return Response
     */
    public function show(Channel $channel)
    {
        $this->authorize('show', $channel);

        $channel->loadContent();

        return $this->success(['channel' => $channel]);
    }

    /**
     * @param CrupdateChannelRequest $request
     * @return Response
     */
    public function store(CrupdateChannelRequest $request)
    {
        $this->authorize('store', Channel::class);

        $channel = app(CrupdateChannel::class)->execute($request->all());

        return $this->success(['channel' => $channel]);
    }

    /**
     * @param Channel $channel
     * @param CrupdateChannelRequest $request
     * @return Response
     */
    public function update(Channel $channel, CrupdateChannelRequest $request)
    {
        $this->authorize('store', $channel);

        $channel = app(CrupdateChannel::class)->execute($request->all(), $channel);

        Cache::forget("channels.$channel->id");

        return $this->success(['channel' => $channel]);
    }

    /**
     * @param Collection $channels
     * @return Response
     */
    public function destroy(Collection $channels)
    {
        $channelIds = $channels->pluck('id');
        $this->authorize('store', [Channel::class, $channelIds]);

        DB::table('channelables')->whereIn('channel_id', $channelIds)->delete();
        $this->channel->whereIn('id', $channelIds)->delete();

        foreach ($channelIds as $channelId) {
            Cache::forget("channels.$channelId");
        }

        return $this->success();
    }

    /**
     * @param Channel $channel
     * @return JsonResponse
     */
    public function autoUpdateChannelContents(Channel $channel)
    {
        $this->authorize('update', $channel);
        $contentType = $this->request->get('contentType');

        if ($contentType && $contentType !== $channel->auto_update) {
            $channel->fill(['auto_update' => $contentType])->save();
        }

        app(UpdateChannelContent::class)
            ->execute($channel);

        $channel->loadContent();

        return $this->success(['channel' => $channel]);
    }

    /**
     * @param Channel $channel
     * @return JsonResponse
     */
    public function detachItem(Channel $channel)
    {
        $this->authorize('update', $channel);

        $modelType = $this->request->get('item')['model_type'];
        // App\Track => tracks
        $relation = strtolower(Str::plural(class_basename($modelType)));

        $channel->$relation()->detach($this->request->get('item')['id']);

        Cache::forget("channels.$channel->id");

        return $this->success();
    }

    /**
     * @param Channel $channel
     * @return JsonResponse
     */
    public function attachItem(Channel $channel)
    {
        $this->authorize('update', $channel);

        $modelType = $this->request->get('item')['model_type'];
        $modelId = (int) $this->request->get('item')['id'];
        // App\Track => tracks
        $relation = strtolower(Str::plural(class_basename($modelType)));

        if ($modelType === Channel::class && $modelId === $channel->id) {
            return $this->error(__("Channel can't be attached to itself."));
        }

        $relationId = $this->request->get('item')['id'];
        if ( ! $channel->$relation()->find($relationId)) {
            $channel->$relation()->attach($relationId);
        }

        Cache::forget("channels.$channel->id");

        return $this->success();
    }

    /**
     * @param Channel $channel
     * @return JsonResponse
     */
    public function changeOrder(Channel $channel) {

        $this->authorize('update', $channel);

        $this->validate($this->request, [
            'ids'   => 'array|min:1',
            'ids.*' => 'integer'
        ]);

        $queryPart = '';
        foreach($this->request->get('ids') as $order => $id) {
            $queryPart .= " when id=$id then $order";
        }

        DB::table('channelables')
            ->where('channel_id', $channel->id)
            ->whereIn('id', $this->request->get('ids'))
            ->update(['order' => DB::raw("(case $queryPart end)")]);

        Cache::forget("channels.$channel->id");

        return $this->success();
    }
}
