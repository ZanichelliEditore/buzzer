<?php

namespace App\Http\Repositories;

use App\Models\Publisher;
use Illuminate\Support\Facades\Hash;

class PublisherRepository implements RepositoryInterface
{
    /**
     * Find a publisher by id
     *
     * @param  int  $id
     * @return App\Models\Publisher
     *
     */
    public function find($id)
    {
        return Publisher::find($id);
    }

    /**
     * Find a publisher by usernamename
     *
     * @param  String  $username
     * @return App\Models\Publisher
     *
     */
    public function findByUsername($username)
    {
        return Publisher::where('username', $username)->first();
    }

    /**
     * Findout if the publisher is linked to the channel
     *
     * @param  Object  $publisher
     * @param  int  $channelId
     * @return boolean
     *
     */
    public function hasChannel($publisher, $channelId)
    {
        return $publisher->channels()->where('channel_id', $channelId)->exists();
    }

    /** Search for all subscribers who match the input query
     *
     * @param  string $query
     * @param  string $orderBy
     * @param  string $order
     * @return Illuminate\Database\Query\Builder
     *
     */
    private function search($query, $orderBy, $order)
    {
        if (!$query) {
            return Publisher::orderBy($orderBy, $order);
        }
        $words = explode(' ', $query);
        $words = array_filter($words);

        $params = [];
        foreach ($words as $key => $word) {
            if ($word) {
                $searchWord = '%' . $word . '%';
                $words[$key] =  "(name LIKE ? or host LIKE ?)";
                array_push($params, $searchWord, $searchWord);
            }
        }
        $searchTerm = implode(' and ', $words);

        return Publisher::whereRaw($searchTerm, $params)
            ->orderBy($orderBy, $order);
    }

    /**
     * Find all the publishers in the database
     *
     * @param  string $orderBy
     * @param  string $order
     * @param  int  $limit
     * @return Illuminate\Database\Eloquent\Collection
     *
     */
    public function all($query, $orderBy, $order, $limit)
    {
        if (isset($limit)) {
            return $this->search($query, $orderBy, $order)->paginate($limit);
        } else {
            return $this->search($query, $orderBy, $order)->get();
        }
    }

    /**
     * Save a publishers in the database
     *
     * @param  App\Models\Publisher $publisher
     * @return Response
     * @throws \Exception
     *
     */
    public function save($publisher)
    {
        return Publisher::create([
            'name' => $publisher->name,
            'host' => $publisher->host,
            'username' => $publisher->username,
            'password' => Hash::make($publisher->password)
        ]);
    }

    /**
     * Delete a publisher from the database
     *
     * @param  App\Models\Publisher $publisher
     * @return Response
     *
     */
    public function delete($publisher)
    {
        return $publisher->delete();
    }
}
