<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateEntryException;
use App\Http\Requests\ChannelPublishRequest;
use App\Http\Repositories\PublisherRepository;
use App\Http\Repositories\ChannelPublishRepository;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Resources\PublisherChannelResource;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;

class ChannelPublishController extends Controller
{
    protected ChannelPublishRepository $channelPublishRepository;
    protected PublisherRepository $publisherRepository;

    public function __construct(RepositoryInterface $channelPublishRepository, PublisherRepository $publisherRepository)
    {
        $this->channelPublishRepository = $channelPublishRepository;
        $this->publisherRepository = $publisherRepository;
    }

    #[ScrambleResponse(status: 201, description: 'The publisher has been successfully registered to the channel', type: 'array{message: string, channelpublish: array{channel_id: int, publisher_id: int}}')]
    public function store(ChannelPublishRequest $request, int $id)
    {
        if (!$this->publisherRepository->find($id)) {
            return $this->error422('publisher_id', "Publisher with id = " . $id . " not Found");
        }
        $channelPublish = $request->only(['channel_id']);
        $channelPublish['publisher_id'] = $id;

        try {
            $this->channelPublishRepository->save((object) $channelPublish);
        } catch (DuplicateEntryException $e) {
            return $this->error409("The subscription already exists", $channelPublish);
        } catch (\Exception $e) {
            return $this->error500(__('messages.SaveError') . ' ' . json_encode($channelPublish));
        }

        return $this->success201("The publisher has been successfully registered to the channel", "channelpublish", $channelPublish);
    }

    public function getChannelPublish(int $id)
    {
        $publisher = $this->publisherRepository->find($id);
        if (!$publisher || count($publisher->registrations) == 0) {
            return $this->error404(__('messages.channelPublish'));
        }
        return PublisherChannelResource::collection($publisher->registrations);
    }

    public function destroy(int $publisher_id, int $channel_id)
    {
        $channelPublish = $this->channelPublishRepository->getByFilter(
            [
                'publisher_id' => $publisher_id,
                'channel_id' => $channel_id
            ]
        );
        if (!$channelPublish) {
            return $this->error404(__('messages.channelPublish'));
        }
        if (!$this->channelPublishRepository->delete($channelPublish)) {
            return $this->error500(__('messages.DeleteError') . $channelPublish->id);
        }

        return $this->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            'object_type' => 'ChannelPublish',
            'object_id' => $channelPublish->id
        ]);
    }
}
