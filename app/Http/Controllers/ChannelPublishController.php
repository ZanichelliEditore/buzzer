<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateEntryException;
use App\Http\Requests\ChannelPublishRequest;
use App\Http\Repositories\PublisherRepository;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Resources\PublisherChannelResource;

class ChannelPublishController extends Controller
{
    protected $channelPublishRepository;
    protected $publisherRepository;

    public function __construct(RepositoryInterface $channelPublishRepository, PublisherRepository $publisherRepository)
    {
        $this->channelPublishRepository = $channelPublishRepository;
        $this->publisherRepository = $publisherRepository;
    }

    /**
     * @OA\Post(
     *      path="/api/publishers/{publisher_id}/channels",
     *      summary="Save new publisher registration to a channel",
     *      tags={"publishers"},
     *      security={{"passport":{}}},
     *      description="Use to register a publisher to a channel",
     *      operationId="ChannelPublishController.store",
     *      @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="publisher id",
     *        name="publisher_id",
     *        @OA\Schema(
     *            type="integer",
     *            minimum=1
     *        )
     *      ),
     *      @OA\RequestBody(
     *          description="Registration object that needs to be created",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                 schema="ChannelPublish",
     *                 type="object",
     *                 required={"channel_id"},
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     example=1
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
    public function store(ChannelPublishRequest $request, $id)
    {
        if (!$this->publisherRepository->find($id)) {
            return response()->error422('publisher_id', "Publisher with id = " . $id . " not Found");
        }
        $channelPublish = $request->only(['channel_id']);
        $channelPublish['publisher_id'] = $id;

        try {
            $this->channelPublishRepository->save((object) $channelPublish);
        } catch (DuplicateEntryException $e) {
            return response()->error409("The subscription already exists", $channelPublish);
        } catch (\Exception $e) {
            return response()->error500(__('messages.SaveError') . ' ' . json_encode($channelPublish));
        }

        return response()->success201("The publisher has been successfully registered to the channel", "channelpublish", $channelPublish);
    }

    /**
     * @OA\Get(
     *     path="/api/publishers/{publisher_id}/channels",
     *     summary="Find a publisher registration by id",
     *     tags={"publishers"},
     *     security={{"passport":{}}},
     *     description="Use to get a publisher registration by id",
     *     operationId="ChannelPublishController.getChannelPublish",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="Publisher id",
     *        name="publisher_id",
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
     * Response to route /api/publishers/{id}/channels
     *
     * @param  int  $id
     * @return $channelPublish
     *
     */
    public function getChannelPublish($id)
    {
        $publisher = $this->publisherRepository->find($id);
        if (!$publisher || count($publisher->registrations) == 0) {
            return response()->error404(__('messages.channelPublish'));
        }
        return PublisherChannelResource::collection($publisher->registrations);
    }

    /**
     * @OA\Delete(
     *      path="/api/publishers/{publisher_id}/channels/{channel_id}",
     *      summary="Delete a publisher registration",
     *      tags={"publishers"},
     *      security={{"passport":{}}},
     *      description="Insert the publisher id and channel id that you want to delete",
     *      operationId="ChannelPublishController.destroy",
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="Publisher id",
     *          name="publisher_id",
     *          @OA\Schema(
     *              type="integer",
     *              minimum=1
     *          )
     *      ),
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="Channel id",
     *          name="channel_id",
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
    public function destroy(int $publisher_id, int $channel_id)
    {
        $channelPublish = $this->channelPublishRepository->getByFilter(
            [
                'publisher_id' => $publisher_id,
                'channel_id' => $channel_id
            ]
        );
        if (!$channelPublish) {
            return response()->error404(__('messages.channelPublish'));
        }
        if (!$this->channelPublishRepository->delete($channelPublish)) {
            return response()->error500(__('messages.DeleteError') . $channelPublish);
        }

        return response()->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            'object_type' => 'ChannelPublish',
            'object_id' => $channelPublish->id
        ]);
    }
}
