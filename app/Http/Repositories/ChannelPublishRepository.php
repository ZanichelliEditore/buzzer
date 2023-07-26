<?php

namespace App\Http\Repositories;

use App\Models\ChannelPublish;
use App\Exceptions\DuplicateEntryException;

class ChannelPublishRepository implements RepositoryInterface
{
    /**
     * Register a publisher to a channel
     *
     * @param  ChannelPublish $channelPublish
     * @return App\Models\ChannelPublish
     * @throws \Exception
     *
     */
    public function save($channelPublish)
    {
        $channelPublisher = ChannelPublish::firstOrNew(
            [
                'channel_id' => $channelPublish->channel_id,
                'publisher_id' => $channelPublish->publisher_id
            ]
        );

        if ($channelPublisher->exists) {
            throw new DuplicateEntryException();
        }

        $channelPublisher->save();
        return $channelPublisher;

    }

    /**
     * Find a publisher registration by id
     *
     * @param  int  $id
     * @return App\Models\ChannelPublish
     *
     */
    public function find($id)
    {
        return ChannelPublish::find($id);
    }

    /**
     * Delete a publisher registration from the database
     *
     * @param  App\Models\ChannelPublish $channelPublish
     * @return Response
     *
     */
    public function delete($channelPublish)
    {
        return $channelPublish->delete();
    }

    /**
     *
     * Find a publisher registration by filter
     *
     * @param  array  $filter
     * @return App\Models\ChannelPublish
     */
    public function getByFilter($filter)
    {
        return ChannelPublish::where($filter)->first();
    }
}
