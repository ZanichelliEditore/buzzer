<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Http\Resources\FailedJobResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Repositories\FailedJobRepository;

class FailedJobController extends Controller
{
    protected FailedJobRepository $failedJobRepository;

    public function __construct(FailedJobRepository $failedJobRepository)
    {
        $this->failedJobRepository = $failedJobRepository;
    }

    public function getList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string',
            'limit' => 'sometimes|integer',
            'order' => 'sometimes|in:asc,desc,ASC,DESC',
            'orderBy' => ['sometimes', Rule::in(Schema::getColumnListing('failed_jobs'))]
        ]);

        if ($validator->fails()) {
            return $this->error422(null, $validator->errors());
        }

        $query = $request->input('q');
        $limit = $request->input('limit', self::PAGINATION);
        $order = $request->input('order', 'ASC');
        $orderBy = $request->input('orderBy', 'id');
        $retriviedFailedJobs = $this->failedJobRepository->all($query, $orderBy, $order, $limit);
        return FailedJobResource::collection($retriviedFailedJobs);
    }

    public function retryJob(int $id)
    {
        $failedJob = $this->failedJobRepository->find($id);
        if (!$failedJob) {
            return $this->error404(__('messages.FailedJob') . $id);
        }
        try {
            $result = Artisan::call('queue:retry', ['id' => $id]);
            if ($result != 0) {
                return $this->error500(__('messages.RetryError') . $failedJob->id);
            }
        } catch (Exception $e) {
            return $this->error500(__('messages.RetryError') . $failedJob->id);
        }

        return $this->success200(__('messages.RetrySuccess'), [
            'action' => 'RETRY',
            'object_type' => 'failedJob',
            'object_id' => $id
        ]);
    }

    public function destroy(int $id)
    {
        $failedJob = $this->failedJobRepository->find($id);
        if (!$failedJob) {
            return $this->error404(__('messages.FailedJob') . $id);
        }

        try {
            $result = Artisan::call('queue:forget', ['id' => $id]);
            if ($result != 0) {
                return $this->error500(__('messages.DeleteError') . $failedJob->id);
            }
        } catch (Exception $e) {
            return $this->error500(__('messages.DeleteError') . $failedJob->id);
        }

        return $this->success200(__('messages.DeleteSuccess'), [
            'action' => 'DELETE',
            'object_type' => 'failedJob',
            'object_id' => $failedJob->id
        ]);
    }

    public function destroyAll()
    {
        $result = Artisan::call('queue:flush');
        if ($result != 0) {
            return $this->error500(__('messages.DeleteError'));
        }
        return $this->success200(__('messages.DeleteSuccess'));
    }

    public function retryAll()
    {
        $result = Artisan::call('queue:lazy-retry');
        if ($result != 0) {
            return $this->error500(__('messages.RetryError') . $result);
        }
        return $this->success200(__('messages.RetrySuccess'));
    }
}
