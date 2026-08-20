<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SubscriberRequest;
use App\Http\Resources\SubscriberResource;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Repositories\SubscriberRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;

class SubscriberController extends Controller
{
    protected SubscriberRepository $subscriberRepository;

    public function __construct(RepositoryInterface $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    public function getList(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit');
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'name');
        $retriviedSubscribers = $this->subscriberRepository->all($query, $orderBy, $order, $limit);
        return SubscriberResource::collection($retriviedSubscribers);
    }

    #[ScrambleResponse(status: 201, description: 'Subscriber successfully saved', type: 'array{message: string, subscriber: SubscriberResource}')]
    public function store(SubscriberRequest $request)
    {
        $subscriber = $request->only([
            'name',
            'host'
        ]);
        $createdSubscriber = null;

        $subscriber['host'] = trim($subscriber['host'], '/') . '/';

        if ($this->subscriberRepository->get($subscriber['host'])) {
            return $this->error422('host', "Duplicated subscriber.");
        }

        try {
            $createdSubscriber = $this->subscriberRepository->save((object) $subscriber);
        } catch (\Exception $e) {
            return $this->error500(__('messages.SaveError') . ' ' . json_encode($subscriber));
        }

        return $this->success201("Subscriber successfully saved", "subscriber", $createdSubscriber);
    }

    public function destroy(int $id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber) {
            return $this->error404(__('messages.Subscriber') . $id);
        }
        if (!$this->subscriberRepository->delete($subscriber)) {
            return $this->error500(__('messages.DeleteError') . $subscriber->id);
        }

        return $this->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            // TO DO: add user id key value pair
            'object_type' => 'subscriber',
            'object_id' => $subscriber->id
        ]);
    }

    public function getSubscriber(int $id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber) {
            return $this->error404(__('messages.Subscriber') . $id);
        }
        return new SubscriberResource($subscriber);
    }

    public function pauseSubscriber(int $id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber) {
            return $this->error404(__('messages.Subscriber') . $id);
        }
        Cache::put(config('cache.subscriber_paused_key_prefix') . $id, true, config('cache.subscriber_paused_ttl'));
        return $this->success204();
    }

    public function restoreSubscriber(int $id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber) {
            return $this->error404(__('messages.Subscriber') . $id);
        }
        Cache::forget(Config::get('cache.subscriber_paused_key_prefix') . $id);
        return $this->success204();
    }
}
