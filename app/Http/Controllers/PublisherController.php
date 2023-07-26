<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Http\Repositories\RepositoryInterface;

class PublisherController extends Controller
{
    protected $publisherRepository;

    public function __construct(RepositoryInterface $publisherRepository)
    {
        $this->publisherRepository = $publisherRepository;
    }

    /**
     * @OA\Get(
     *      path="/api/publishers",
     *      summary="List of all publishers",
     *      tags={"publishers"},
     *      security={{"passport":{}}},
     *      description="Use to get the list of all publishers",
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
        $retriviedPublishers = $this->publisherRepository->all($query, $orderBy, $order, $limit);
        return PublisherResource::collection($retriviedPublishers);
    }

    /**
     * @OA\Post(
     *      path="/api/publishers",
     *      summary="Save new publisher",
     *      tags={"publishers"},
     *      security={{"passport":{}}},
     *      description="Use to store a new publisher",
     *      @OA\RequestBody(
     *          description="Publisher object that needs to be created",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  schema="Publisher",
     *                  type="object",
     *                  @OA\Property(
     *                      property="name",
     *                      type="string",
     *                      example="publisher1"
     *                  ),
     *                  @OA\Property(
     *                      property="host",
     *                      type="string",
     *                      example="https://my-host.it"
     *                  ),
     *                  @OA\Property(
     *                      property="username",
     *                      type="string",
     *                      example="username"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string",
     *                      example="password"
     *                  ),
     *               ),
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
    public function store(PublisherRequest $request)
    {
        $publisher = $request->only([
            'name',
            'host',
            'username',
            'password'
        ]);

        try {
            $this->publisherRepository->save((object) $publisher);
        } catch (\Exception $e) {
            return response()->error500(__('messages.SaveError') . ' ' . json_encode($publisher));
        }

        return response()->success201("Publisher successfully saved", "publisher", $publisher);
    }

    /**
     * @OA\Delete(
     *      path="/api/publishers/{id}",
     *      summary="Delete the publisher",
     *      tags={"publishers"},
     *      security={{"passport":{}}},
     *      description="Insert the publisher id that you want to delete",
     *      operationId="PublisherController.destroy",
     *      @OA\Parameter(
     *          in="path",
     *          required=true,
     *          description="publisher id",
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
        $publisher = $this->publisherRepository->find($id);
        if (!$publisher) {
            return response()->error404(__('messages.Publisher') . $id);
        }
        if (!$this->publisherRepository->delete($publisher)) {
            return response()->error500(__('messages.DeleteError') . $publisher);
        }

        return response()->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            'object_type' => 'publisher',
            'object_id' => $publisher->id
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/publishers/{id}",
     *     summary="Find a publisher by id",
     *     tags={"publishers"},
     *     security={{"passport":{}}},
     *     description="Use to get a publisher by id",
     *     operationId="PublisherController.getPublisher",
     *     @OA\Parameter(
     *        in="path",
     *        required=true,
     *        description="publisher id",
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
     * Response to route /api/publishers/{id}
     *
     * @param  int  $id
     * @return $publisher
     *
     */
    public function getPublisher($id)
    {
        $publisher = $this->publisherRepository->find($id);;
        if (!$publisher) {
            return response()->error404(__('messages.Publisher') . $id);
        }
        return new PublisherResource($publisher);
    }
}
