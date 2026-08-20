<?php

namespace App\Http\Controllers;

use App\Http\Repositories\PublisherRepository;
use Illuminate\Http\Request;
use App\Http\Requests\PublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Http\Repositories\RepositoryInterface;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;

class PublisherController extends Controller
{
    protected PublisherRepository $publisherRepository;

    public function __construct(RepositoryInterface $publisherRepository)
    {
        $this->publisherRepository = $publisherRepository;
    }

    public function getList(Request $request)
    {
        $query = $request->input('q');
        $limit = $request->input('limit');
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'name');
        $retriviedPublishers = $this->publisherRepository->all($query, $orderBy, $order, $limit);
        return PublisherResource::collection($retriviedPublishers);
    }

    #[ScrambleResponse(status: 201, description: 'Publisher successfully saved', type: 'array{message: string, publisher: PublisherResource}')]
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
            return $this->error500(__('messages.SaveError') . ' ' . json_encode($publisher));
        }

        return $this->success201("Publisher successfully saved", "publisher", $publisher);
    }

    public function destroy(int $id)
    {
        $publisher = $this->publisherRepository->find($id);
        if (!$publisher) {
            return $this->error404(__('messages.Publisher') . $id);
        }
        if (!$this->publisherRepository->delete($publisher)) {
            return $this->error500(__('messages.DeleteError') . $publisher->id);
        }

        return $this->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            'object_type' => 'publisher',
            'object_id' => $publisher->id
        ]);
    }

    public function getPublisher(int $id)
    {
        $publisher = $this->publisherRepository->find($id);
        if (!$publisher) {
            return $this->error404(__('messages.Publisher') . $id);
        }
        return new PublisherResource($publisher);
    }
}
