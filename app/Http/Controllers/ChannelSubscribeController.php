<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Exceptions\DuplicateEntryException;
use App\Http\Repositories\ChannelRepository;
use App\Http\Repositories\ChannelSubscribeRepository;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Requests\ChannelSubscribeRequest;
use App\Http\Repositories\SubscriberRepository;
use App\Http\Resources\SubscriberChannelResource;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;

class ChannelSubscribeController extends Controller
{
    protected ChannelSubscribeRepository $channelSubscribeRepository;
    protected SubscriberRepository $subscriberRepository;
    protected ChannelRepository $channelRepository;

    const CHANNEL_CACHE_KEY_PREFIX = "channel.";

    public function __construct(RepositoryInterface $channelSubscribeRepository, SubscriberRepository $subscriberRepository, ChannelRepository $channelRepository)
    {
        $this->channelSubscribeRepository = $channelSubscribeRepository;
        $this->subscriberRepository = $subscriberRepository;
        $this->channelRepository = $channelRepository;
    }

    #[ScrambleResponse(status: 201, description: 'The subscriber has been successfully registered to the channel', type: 'array{message: string, channelsubscribe: array{channel_id: int, endpoint: string, authentication: string, username: string, password: string,subscriber_id: int }}')]
    public function store(ChannelSubscribeRequest $request, int $id)
    {
        if (!$this->subscriberRepository->find($id)) {
            return $this->error422('subscriber_id', "Subscriber with id = " . $id . " not Found");
        }

        if (!$channel = $this->channelRepository->find($request->get("channel_id"))) {
            return $this->error422('channel_id', "Channel with id = " . $request->get("channel_id") . " not found");
        }

        $channelSubscribe = $request->only([
            'channel_id',
            'endpoint',
            'authentication',
            'username',
            'password'
        ]);
        $channelSubscribe['subscriber_id'] = $id;


        $channelSubscribe['endpoint'] = ltrim($channelSubscribe['endpoint'], '/');

        Cache::forget(Config::get('cache.channel_key_prefix') . $channel->name);

        try {
            $this->channelSubscribeRepository->save((object) $channelSubscribe);
        } catch (DuplicateEntryException $e) {
            return $this->error409("The subscription already exists", $channelSubscribe);
        } catch (\Exception $e) {
            return $this->error500(__('messages.SaveError') . ' ' . json_encode($channelSubscribe));
        }

        return $this->success201("The subscriber has been successfully registered to the channel", "channelsubscribe", $channelSubscribe);
    }

    public function getChannelSubscribe(int $id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber || count($subscriber->registrations) == 0) {
            return $this->error404(__('messages.subscriber') . $id);
        }
        return SubscriberChannelResource::collection($subscriber->registrations);
    }

    public function destroy(int $id)
    {
        try {
            $channelSubscribe = $this->channelSubscribeRepository->find($id);
            if (!$channelSubscribe) {
                return $this->error404(__('messages.channelSubscribe'));
            }

            Cache::forget(Config::get('cache.channel_key_prefix') . $channelSubscribe->channel->name);

            $this->channelSubscribeRepository->delete($channelSubscribe);

            return $this->success200(__('messages.DeleteSuccess'), [
                'action' => 'DELETE',
                'object_type' => 'ChannelSubscribe',
                'object_id' => $channelSubscribe->id
            ]);
        } catch (\Exception $e) {
            return $this->error500(__('messages.DeleteError') . $id);
        }
    }
}
