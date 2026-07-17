<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\SendMessageEvent;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ChannelRequest;
use App\Http\Requests\MessageRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\PublisherResource;
use App\Http\Repositories\ChannelRepository;
use App\Http\Requests\MessageChannelRequest;
use App\Http\Repositories\PublisherRepository;
use App\Http\Resources\ChannelSubscribeResource;
use App\Http\Repositories\ChannelSubscribeRepository;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;

class ChannelController extends Controller
{
    protected ChannelRepository $channelRepository;
    protected ChannelSubscribeRepository $channelSubscribeRepository;
    protected PublisherRepository $publisherRepository;

    public function __construct(ChannelRepository $channelRepository, ChannelSubscribeRepository $ChannelSubscribeRepository, PublisherRepository $publisherRepository)
    {
        $this->channelRepository = $channelRepository;
        $this->publisherRepository = $publisherRepository;
        $this->channelSubscribeRepository = $ChannelSubscribeRepository;
    }

    public function getList(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit', self::PAGINATION);
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'name');
        $retriviedChannels = $this->channelRepository->all($query, $orderBy, $order, $limit);
        return ChannelResource::collection($retriviedChannels);
    }

    public function getChannel($id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return $this->error404(__('messages.Channel') . $id);
        }
        return  new ChannelResource($channel);
    }

    #[ScrambleResponse(status: 201, description: 'Channel created successfully', type: 'array{message: string, channel: array{name: string, priority: string}}')]
    public function store(ChannelRequest $request)
    {
        $channel = $request->only(['name', 'priority']);

        try {
            $this->channelRepository->save((object) $channel);
        } catch (\Exception $e) {
            return $this->error500(__('messages.SaveError') . ' ' . json_encode($channel));
        }

        return $this->success201("Channel successfully saved", "channel", $channel);
    }

    public function destroy(int $id)
    {
        $channel = $this->channelRepository->find($id);

        if (!$channel) {
            return $this->error404(__('messages.Channel') . $id);
        }

        Cache::forget(Config::get('cache.channel_key_prefix') . $channel->name);

        if (!$this->channelRepository->delete($channel)) {
            return $this->error500(__('messages.DeleteError') . $channel->id);
        }

        return $this->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            // TO DO: add user id key value pair
            'object_type' => 'channel',
            'object_id' => $channel->id
        ]);
    }

    public function getChannelSubscribers(int $id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return $this->error404("Channel " . $id);
        }
        return ChannelSubscribeResource::collection($channel->registrations);
    }

    public function getChannelPublishers(int $id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return $this->error404("Channel " . $id);
        }
        return PublisherResource::collection(($channel->publishers)->unique());
    }

    public function sendMessage(MessageChannelRequest $request)
    {
        $channel = $this->getCachedChannelByName($request->channel);

        return $this->sendMessageTo($request, $channel);
    }

    public function sendMessageToChannel(MessageRequest $request, string $channelName)
    {
        $channel = $this->getCachedChannelByName($channelName);

        if (!$channel) {
            return $this->error404(__('messages.Channel'));
        }
        return $this->sendMessageTo($request, $channel);
    }

    private function sendMessageTo(Request $request, $channel)
    {
        $publisher = Auth::guard('api')->user();
        if (!$this->publisherRepository->hasChannel($publisher, $channel->id)) {
            return $this->error403(
                "Not authorized to send message on requested channel",
                [
                    'publisherId' => $publisher->id,
                    'requestedChannelId' => $channel->id
                ]
            );
        }

        $message = new Message($request->message);
        foreach ($channel->subscribers as $subscriber) {
            $relations = $this->channelSubscribeRepository->where($channel->id, $subscriber->id);
            foreach ($relations as $relation) {
                event(new SendMessageEvent($message, $subscriber->host, $relation, $channel->priority, $channel?->name ?? "", $subscriber->name));
            }
        }
        return $this->success200("Message dispatched");
    }

    private function getCachedChannelByName(string $channelName)
    {
        $cacheKey = Config::get('cache.channel_key_prefix') . $channelName;

        $cachedChannel = Cache::get($cacheKey);
        if ($cachedChannel) return json_decode($cachedChannel);

        $channel = $this->channelRepository->findByName($channelName);
        if (!$channel) return false;

        $cacheData = (object)[
            "id" => $channel->id,
            "name" => $channel->name,
            "subscribers" => [],
            "priority" => $channel->priority
        ];
        foreach ($channel->subscribers->unique("id") as $subscriber) {
            $cacheData->subscribers[] = (object)[
                "id" => $subscriber->id,
                "name" => $subscriber->name,
                "host" => $subscriber->host
            ];
        }
        Cache::forever($cacheKey, json_encode($cacheData));

        return $cacheData;
    }
}
