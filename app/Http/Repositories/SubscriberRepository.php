<?php

namespace App\Http\Repositories;

use App\Models\Subscriber;

class SubscriberRepository implements RepositoryInterface
{
    /**
     * Find a subscriber by id
     *
     * @param  int  $id
     * @return App\Models\Subscriber
     *
     */
    public function find($id)
    {
        return Subscriber::find($id);
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
            return Subscriber::orderBy($orderBy, $order);
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

        return Subscriber::whereRaw($searchTerm, $params)
            ->orderBy($orderBy, $order);
    }

    /**
     * Find all the subscribers in the database
     *
     * @param  string $query
     * @param  string $orderBy
     * @param  string $order
     * @param  ?int  $limit
     * @return Illuminate\Database\Eloquent\Collection
     *
     */
    public function all($query, $orderBy, $order, ?int $limit)
    {
        if (isset($limit)) {
            return $this->search($query, $orderBy, $order)->paginate($limit);
        } else {
            return $this->search($query, $orderBy, $order)->get();
        }
    }

    /**
     * Save a subscriber in the database
     *
     * @param  App\Models\Subscriber $subscriber
     * @return Response
     * @throws \Exception
     *
     */
    public function save($subscriber)
    {
        return Subscriber::create([
            'name' => $subscriber->name,
            'host' => $subscriber->host
        ]);
    }

    /**
     * Delete a subscriber from the database
     *
     * @param  App\Models\Subscriber $subscriber
     * @return Response
     *
     */
    public function delete($subscriber)
    {
        return $subscriber->delete();
    }

    /**
     * Find a subscriber by host
     *
     * @param  string  $host
     * @return App\Models\Subscriber
     *
     */
    public function get($host)
    {
        return Subscriber::where('host', $host)->first();
    }
}
