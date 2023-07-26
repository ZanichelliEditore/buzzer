<?php

namespace App\Http\Repositories;

use App\Models\Channel;

class ChannelRepository implements RepositoryInterface
{
    /**
     * Find a channel by id
     *
     * @param  int  $id
     * @return App\Models\Channel
     *
     */
    public function find($id)
    {
        return Channel::find($id);
    }

    /**
     * Find all channels
     *
     * @param  String  $orderBy
     * @param  String  $order
     * @param  int  $limit
     * @return App\Models\Channel
     *
     */
    public function all($query, $orderBy, $order, $limit)
    {
        if (!$query) {
            return Channel::orderBy($orderBy, $order)
                ->paginate($limit);
        }

        return Channel::where('name', 'LIKE', '%' . $query . '%')
            ->orderBy($orderBy, $order)
            ->paginate($limit);
    }

     /**
     * Find all channels names
     *
     * @return App\Models\Channel
     *
     */
    public function getAllNames() 
    {
        return Channel::pluck('name');
    }

    /**
     * Find a channel by name
     *
     * @param  int  $name
     * @return App\Models\Channel
     *
     */
    public function findByName($name)
    {
        return Channel::where('name', $name)->first();
    }

    /**
     * Stores new channel in database
     *
     * @param  object $channel
     * @return boolean
     * @throws \Exception
     *
     */
    public function save($channel)
    {
        return Channel::create([
            'name' => $channel->name,
            'priority' => $channel->priority
        ]);
    }

    /**
     * Delete channel from database
     *
     * @param  Channel $channel
     * @return boolean
     *
     */
    public function delete($channel)
    {
        return $channel->delete();
    }
}
