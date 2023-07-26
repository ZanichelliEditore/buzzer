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

class ChannelController extends Controller
{
    protected $channelRepository;
    protected $channelSubscribeRepository;
    protected $publisherRepository;

    public function __construct(ChannelRepository $channelRepository, ChannelSubscribeRepository $ChannelSubscribeRepository, PublisherRepository $publisherRepository)
    {
        $this->channelRepository = $channelRepository;
        $this->publisherRepository = $publisherRepository;
        $this->channelSubscribeRepository = $ChannelSubscribeRepository;
    }

    /**
     * @OA\Get(
     *      path="/api/channels",
     *      summary="List of all channels",
     *      tags={"channels"},
     *      security={{"passport":{}}},
     *      description="Use to get the list of all channels",
     *      @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="values to filter returned data (name values)",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *      ),
     *      @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="maximum number of results to return",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *             minimum=1
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="type of order: ASC, DESC",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="field to order: id - name(default) - created_at - updated_at",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          ref="#/components/responses/Success200"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          ref="#/components/responses/Error500",
     *      )
     *
     * )
     */
    public function getList(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit', self::PAGINATION);
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'name');
        $retriviedChannels = $this->channelRepository->all($query, $orderBy, $order, $limit);
        return ChannelResource::collection($retriviedChannels);
    }


    /**
     * @OA\Get(
     *     path="/api/channels/{id}",
     *     summary="Find a channel by id",
     *     tags={"channels"},
     *     security={{"passport":{}}},
     *     description="Use to get a channel by id",
     *     operationId="channelController.getchannel",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="channel id",
     *        name="id",
     *        @OA\Schema(
     *            type="integer",
     *            minimum=1
     *        )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/Success200"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         ref="#/components/responses/Error404"
     *     )
     * )
     *
     * Response to route /api/channels/{id}
     *
     * @param  int  $id
     * @return ChannelResource $channel
     *
     */
    public function getChannel($id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return response()->error404(__('messages.Channel') . $id);
        }
        return  new ChannelResource($channel);
    }

    /**
     * @OA\Post(
     *      path="/api/channels",
     *      summary="Save new channel",
     *      tags={"channels"},
     *      security={{"passport":{}}},
     *      description="Use to store a new channel",
     *      @OA\RequestBody(
     *          description="Channel object that needs to be created",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                 schema="Channel",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="channel1"
     *                 ),
     *                 @OA\Property(
     *                     property="priority",
     *                     type="string",
     *                     example="default",
     *                     enum={"high", "medium", "low", "default"},
     *                 )
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          ref="#/components/responses/Success201",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          ref="#/components/responses/Error500",
     *      ),
     *      @OA\Response(
     *          response=422,
     *          ref="#/components/responses/Error422",
     *      )
     * )
     */
    public function store(ChannelRequest $request)
    {
        $channel = $request->only(['name', 'priority']);

        try {
            $this->channelRepository->save((object) $channel);
        } catch (\Exception $e) {
            return response()->error500(__('messages.SaveError') . ' ' . json_encode($channel));
        }

        return response()->success201("Channel successfully saved", "channel", $channel);
    }

    /**
     * @OA\Delete(
     *      path="/api/channels/{id}",
     *      summary="Delete the channel",
     *      tags={"channels"},
     *      security={{"passport":{}}},
     *      description="Delete channel and its relations",
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="channel id",
     *          name="id",
     *          @OA\Schema(
     *              type="integer",
     *              minimum=1
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          ref="#/components/responses/Error500"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          ref="#/components/responses/Error404"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          ref="#/components/responses/Success200"
     *      )
     * )
     */
    public function destroy(int $id)
    {
        $channel = $this->channelRepository->find($id);

        if (!$channel) {
            return response()->error404(__('messages.Channel') . $id);
        }

        Cache::forget(Config::get('cache.channel_key_prefix') . $channel->name);

        if (!$this->channelRepository->delete($channel)) {
            return response()->error500(__('messages.DeleteError') . $channel);
        }

        return response()->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            // TO DO: add user id key value pair
            'object_type' => 'channel',
            'object_id' => $channel->id
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/channels/{id}/subscribers",
     *     summary="List of all subscribers of a channel",
     *     tags={"channels"},
     *     security={{"passport":{}}},
     *     description="Use to get the list of all the subscribers of a channel",
     *     operationId="ChannelController.getChannelSubscribers",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="Channel id",
     *        name="id",
     *        @OA\Schema(
     *            type="integer",
     *            minimum=1
     *        )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/Success200"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         ref="#/components/responses/Error404"
     *     )
     * )
     *
     * Response to route /api/channels/{id}/subscribers
     *
     * @param  int  $id
     * @return $subscriber
     *
     */
    public function getChannelSubscribers($id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return response()->error404("Channel " . $id);
        }
        return ChannelSubscribeResource::collection($channel->registrations);
    }

    /**
     * @OA\Get(
     *     path="/api/channels/{id}/publishers",
     *     summary="List of all publishers of a channel",
     *     tags={"channels"},
     *     security={{"passport":{}}},
     *     description="Use to get the list of all the publishers of a channel",
     *     operationId="ChannelController.getChannelPublishers",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="Channel id",
     *        name="id",
     *        @OA\Schema(
     *            type="integer",
     *            minimum=1
     *        )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/Success200"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         ref="#/components/responses/Error404"
     *     )
     * )
     *
     * Response to route /api/channels/{id}/publishers
     *
     * @param  int  $id
     * @return $publisher
     *
     */
    public function getChannelPublishers($id)
    {
        $channel = $this->channelRepository->find($id);
        if (!$channel) {
            return response()->error404("Channel " . $id);
        }
        return PublisherResource::collection(($channel->publishers)->unique());
    }

    /**
     * @OA\Post(
     *     path="/api/sendMessage",
     *     summary="Send a message",
     *     tags={"messages"},
     *     description="Use to send messages to the subscribers of a channel",
     *     operationId="ChannelController.sendMessage",
     *     security = {{"basicAuth": {}}},
     *     @OA\RequestBody(
     *         description="Message that needs to be sent and channel name",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                schema="SendMessage",
     *                type="object",
     *                required={"channel", "message"},
     *                @OA\Property(
     *                    property="channel",
     *                    type="string",
     *                    example="channel name"
     *                ),
     *                @OA\Property(
     *                    property="message",
     *                    type="string",
     *                    example="message example"
     *                ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/Success200"
     *     ),
     *      @OA\Response(
     *         response=422,
     *         ref="#/components/responses/Error422"
     *     )
     * )
     *
     * Response to route /api/sendMessage
     *
     * @param  MessageChannelRequest $request
     * @return Response
     *
     */
    public function sendMessage(MessageChannelRequest $request)
    {
        $channel = $this->getCachedChannelByName($request->channel);

        return $this->sendMessageTo($request, $channel);
    }

    /**
     * @OA\Post(
     *     path="/api/sendMessage/{channelName}",
     *     summary="Send a message to Channel",
     *     tags={"messages"},
     *     description="Use to send message to the subscribers of a channel",
     *     operationId="ChannelController.SendMessageToChannel",
     *     security = {{"basicAuth": {}}},
     *     @OA\Parameter(
     *         in="path",
     *         required=true,
     *         description="channel name",
     *         name="channelName",
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Message that needs to be sent",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *               schema="Message",
     *               type="object",
     *               required={"message"},
     *               @OA\Property(
     *                   property="message",
     *                   type="string",
     *                   example="message example"
     *               ),
     *            )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/Success200"
     *     ),
     *      @OA\Response(
     *         response=422,
     *         ref="#/components/responses/Error422"
     *     )
     * )
     *
     * Response to route /api/sendMessage/{channelName}
     *
     * @param  MessageRequest $request
     * @param  String $channelName
     * @return Response
     *
     */
    public function SendMessageToChannel(MessageRequest $request, $channelName)
    {
        $channel = $this->getCachedChannelByName($channelName);

        if (!$channel) {
            return response()->error404(__('messages.Channel'));
        }
        return $this->sendMessageTo($request, $channel);
    }

    /**
     * Manage SendMessage request
     *
     * @param  Request $request
     * @param  mixed $channel
     * @return Response
     *
     */
    private function sendMessageTo($request, $channel)
    {
        $publisher = Auth::guard('api')->user();
        if (!$this->publisherRepository->hasChannel($publisher, $channel->id)) {
            return response()->error403(
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
                event(new SendMessageEvent($message, $subscriber->host, $relation, $channel->priority));
            }
        }
        return response()->json([
            "message" => "Message dispatched",
        ], 200);
    }

    /**
     * Save or retrieve channel and its subscribers from cache
     *
     * @param string $channelName
     * @return mixed|boolean
     */
    private function getCachedChannelByName($channelName)
    {
        $cacheKey = Config::get('cache.channel_key_prefix') . $channelName;

        $cachedChannel = Cache::get($cacheKey);
        if ($cachedChannel) return json_decode($cachedChannel);

        $channel = $this->channelRepository->findByName($channelName);
        if (!$channel) return false;

        $cacheData = (object)[
            "id" => $channel->id,
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
