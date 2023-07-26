<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SubscriberRequest;
use App\Http\Resources\SubscriberResource;
use App\Http\Repositories\RepositoryInterface;

class SubscriberController extends Controller
{
    protected $subscriberRepository;

    public function __construct(RepositoryInterface $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    /**
     * @OA\Get(
     *      path="/api/subscribers",
     *      summary="List of all subscribers",
     *      tags={"subscribers"},
     *      security={{"passport":{}}},
     *      description="Use to get the list of all subscribers",
     *      @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="values to filter returned data",
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
     *             format="int32"
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
     *         description="field to order: id - name(default) - host - created_at - updated_at",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          ref="#/components/responses/Success200"
     *      )
     *
     * )
     */
    public function getList(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit');
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'name');
        $retriviedSubscribers = $this->subscriberRepository->all($query, $orderBy, $order, $limit);
        return SubscriberResource::collection($retriviedSubscribers);
    }

    /**
     * @OA\Post(
     *      path="/api/subscribers",
     *      summary="Save new subscriber",
     *      tags={"subscribers"},
     *      security={{"passport":{}}},
     *      description="Use to store a new subscriber",
     *      @OA\RequestBody(
     *          description="Subscriber object that needs to be created",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  schema="Subscriber",
     *                  type="object",
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="subscriber1"
     *                  ),
     *                  @OA\Property(
     *                      property="host",
     *                      type="string",
     *                      example="https://host1-test/"
     *                  )
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
    public function store(SubscriberRequest $request)
    {
        $subscriber = $request->only([
            'name',
            'host'
        ]);
        $createdSubscriber = null;

        $subscriber['host'] = trim($subscriber['host'], '/') . '/';

        if ($this->subscriberRepository->get($subscriber['host'])) {
            return response()->error422('host', "Duplicated subscriber.");
        }

        try {
            $createdSubscriber = $this->subscriberRepository->save((object) $subscriber);
        } catch (\Exception $e) {
            return response()->error500(__('messages.SaveError') . ' ' . json_encode($subscriber));
        }

        return response()->success201("Subscriber successfully saved", "subscriber", $createdSubscriber);
    }

    /**
     * @OA\Delete(
     *      path="/api/subscribers/{id}",
     *      summary="Delete the subscriber",
     *      tags={"subscribers"},
     *      security={{"passport":{}}},
     *      description="Insert the subscriber id that you want to delete",
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="subscriber id",
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
        $subscriber = $this->subscriberRepository->find($id);
        if (!$subscriber) {
            return response()->error404(__('messages.Subscriber') . $id);
        }
        if (!$this->subscriberRepository->delete($subscriber)) {
            return response()->error500(__('messages.DeleteError') . $subscriber);
        }

        return response()->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            // TO DO: add user id key value pair
            'object_type' => 'subscriber',
            'object_id' => $subscriber->id
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/subscribers/{id}",
     *     summary="Find a subscriber by id",
     *     tags={"subscribers"},
     *     security={{"passport":{}}},
     *     description="Use to get a subscriber by id",
     *     operationId="SubscriberController.getSubscriber",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="Subscriber id",
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
     * Response to route /api/subscribers/{id}
     *
     * @param  int  $id
     * @return $subscriber
     *
     */
    public function getSubscriber($id)
    {
        $subscriber = $this->subscriberRepository->find($id);;
        if (!$subscriber) {
            return response()->error404(__('messages.Subscriber') . $id);
        }
        return new SubscriberResource($subscriber);
    }
}
