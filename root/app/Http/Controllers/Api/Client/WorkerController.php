<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\WorkerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkerController extends Controller
{
    protected $workerService;

    public function __construct(WorkerService $workerService)
    {
        $this->workerService = $workerService;
    }

    public function startWorker(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = [
                'user_id' => auth()->user()->user_id,
                'ip' => $request->ip(),
            ];

            $response = $this->workerService->startWorker($data);

            DB::commit();

            return $this->responseSuccess(
                $response,
                Response::HTTP_CREATED,
                'Tạo mới nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
