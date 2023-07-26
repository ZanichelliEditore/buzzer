<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Exceptions\DuplicateEntryException;
use App\Http\Repositories\ChannelRepository;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Requests\ChannelSubscribeRequest;
use App\Http\Repositories\SubscriberRepository;
use App\Http\Resources\SubscriberChannelResource;

class ChannelSubscribeController extends Controller
{
    protected $channelSubscribeRepository;
    protected $subscriberRepository;
    protected $channelRepository;

    const CHANNEL_CACHE_KEY_PREFIX = "channel.";

    public function __construct(RepositoryInterface $channelSubscribeRepository, SubscriberRepository $subscriberRepository, ChannelRepository $channelRepository)
    {
        $this->channelSubscribeRepository = $channelSubscribeRepository;
        $this->subscriberRepository = $subscriberRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * @OA\Post(
     *      path="/api/subscribers/{subscriber_id}/channels",
     *      summary="Save new subscriber registration to a channel",
     *      tags={"subscribers"},
     *      security={{"passport":{}}},
     *      description="Use to register a subscriber to a channel",
     *      operationId="ChannelSubscribeController.store",
     *      @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="Subscribers id",
     *        name="subscriber_id",
     *        @OA\Schema(
     *            type="integer",
     *            minimum=1
     *        )
     *      ),
     *      @OA\RequestBody(
     *          description="Registration object that needs to be created",
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(
     *                  schema="ChannelSubscribe",
     *                  type="object",
     *                  required={"channel_id", "endpoint", "authentication"},
     *                  @OA\Property(
     *                      property="channel_id",
     *                      type="integer",
     *                      example=1
     *                  ),
     *                  @OA\Property(
     *                      property="endpoint",
     *                      type="string",
     *                      example="api/test"
     *                  ),
     *                  @OA\Property(
     *                      property="authentication",
     *                      type="string",
     *                      enum={"NONE", "OAUTH2", "BASIC"},
     *                      default="NONE"
     *                  ),
     *                  @OA\Property(
     *                      property="username",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string",
     *                      format="password"
     *                  )
     *             ),
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
     *          response=409,
     *          ref="#/components/responses/Error409",
     *      ),
     *      @OA\Response(
     *          response=422,
     *          ref="#/components/responses/Error422",
     *      )
     * )
     */
    public function store(ChannelSubscribeRequest $request, int $id)
    {
        if (!$this->subscriberRepository->find($id)) {
            return response()->error422('subscriber_id', "Subscriber with id = " . $id . " not Found");
        }

        if (!$channel = $this->channelRepository->find($request->get("channel_id"))) {
            return response()->error422('channel_id', "Channel with id = " . $request->get("channel_id") . " not found");
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
            return response()->error409("The subscription already exists", $channelSubscribe);
        } catch (\Exception $e) {
            return response()->error500(__('messages.SaveError') . ' ' . json_encode($channelSubscribe));
        }

        return response()->success201("The subscriber has been successfully registered to the channel", "channelsubscribe", $channelSubscribe);
    }

    /**
     * @OA\Get(
     *     path="/api/subscribers/{id}/channels",
     *     summary="Find a subscriber registration by id of subscribers",
     *     tags={"subscribers"},
     *     security={{"passport":{}}},
     *     description="Use to get a subscriber registrations by id",
     *     operationId="ChannelSubscribeController.getChannelSubscribe",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="subscriber id",
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
     * Response to route /api/subscribers/{id}/channels
     *
     * @param  int  $id subscriber_id
     * @return $channelSubscribe
     *
     */
    public function getChannelSubscribe($id)
    {
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber || count($subscriber->registrations) == 0) {
            return response()->error404(__('messages.subscriber') . $id);
        }
        return SubscriberChannelResource::collection($subscriber->registrations);
    }

    /**
     * @OA\Delete(
     *      path="/api/channel-subscriber/{id}",
     *      summary="Delete a subscriber registration",
     *      tags={"channel-subscriber"},
     *      security={{"passport":{}}},
     *      description="Insert the channel-subscriber relation id that you want to delete",
     *      operationId="ChannelSubscribeController.destroy",
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="channel-subscriber id",
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
        try {
            $channelSubscribe = $this->channelSubscribeRepository->find($id);
            if (!$channelSubscribe) {
                return response()->error404(__('messages.channelSubscribe'));
            }

            Cache::forget(Config::get('cache.channel_key_prefix') . $channelSubscribe->channel->name);

            $this->channelSubscribeRepository->delete($channelSubscribe);

            return response()->success200(__('messages.DeleteSuccess'), [
                'action' => 'DELETE',
                'object_type' => 'ChannelSubscribe',
                'object_id' => $channelSubscribe->id
            ]);
        } catch (\Exception $e) {
            return response()->error500(__('messages.DeleteError') . $channelSubscribe);
        }
    }
}
