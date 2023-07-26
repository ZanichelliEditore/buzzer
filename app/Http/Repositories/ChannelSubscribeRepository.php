<?php

namespace App\Http\Repositories;

use App\Models\ChannelSubscribe;
use App\Exceptions\DuplicateEntryException;

class ChannelSubscribeRepository implements RepositoryInterface
{
    /**
     * Find distinct subscribers by channel id
     *
     * @param  int  $channelId
     * @return App\Models\ChannelSubscribe
     *
     */
    public function distinctSubscribers($channelId)
    {
        return ChannelSubscribe::where('channel_id', $channelId)->select('subscriber_id')
            ->distinct()
            ->get();
    }

    /**
     * Find a channel_repository by channel and subscriber id
     *
     * @param  int  $channelId
     * @param  int $subscriberId
     * @return App\Models\ChannelSubscribe
     *
     */
    public function where($channelId, $subscriberId)
    {
        return ChannelSubscribe::where('channel_id', $channelId)
            ->where('subscriber_id', $subscriberId)->get();
    }
    /**
     * Find a channelsubscribe by id
     *
     * @param  int  $id
     * @return App\Models\ChannelSubscribe
     *
     */
    public function find($id)
    {
        return ChannelSubscribe::find($id);
    }

    /**
     * Register a subscriber to a channel
     *
     * @param  ChannelSubscribe $channelSubscribe
     * @return App\Models\ChannelSubscribe
     * @throws DuplicateEntryException
     * @throws \Exception
     *
     */
    public function save($channelSubscribe)
    {
        $password = null;
        if (isset($channelSubscribe->password) && $channelSubscribe->authentication == 'OAUTH2') {
            $password = openssl_encrypt($channelSubscribe->password, 'AES256', env('APP_KEY'), $options = 0, env('CRYPT_KEY'));
        } else if (isset($channelSubscribe->password) && $channelSubscribe->authentication == 'BASIC') {
            $password = encrypt($channelSubscribe->password);
        }
        $channelSubscribe = ChannelSubscribe::firstOrNew(
            [
                'channel_id' => $channelSubscribe->channel_id,
                'subscriber_id' => $channelSubscribe->subscriber_id,
                'endpoint' => $channelSubscribe->endpoint,
            ],
            [
                'authentication' => $channelSubscribe->authentication,
                'username' => isset($channelSubscribe->username) ? $channelSubscribe->username : null,
                'password' => $password,
            ]
        );

        if ($channelSubscribe->exists) {
            throw new DuplicateEntryException();
        }

        $channelSubscribe->save();
        return $channelSubscribe;
    }

    /**
     * Delete a subscriber registration from the database
     *
     * @param  App\Models\ChannelSubscribe $channelSubscribe
     * @return Response
     *
     */
    public function delete($channelSubscribe)
    {
        return $channelSubscribe->delete();
    }
}
