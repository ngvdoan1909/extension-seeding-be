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
            return $this->responseError(500, $e->getMessage());
        }
    }

    public function cancelWorker(string $id)
    {
        DB::beginTransaction();
        try {
            $this->workerService->cancelWorker($id);

            DB::commit();
            return $this->responseSuccess(
                [],
                Response::HTTP_OK,
                'Hủy nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(500, $e->getMessage());
        }
    }

    public function checkPhone(Request $request)
    {
        try {
            $data = $request->all();
            $data['ip'] = $request->ip();

            $response = $this->workerService->checkPhone($data);

            return $this->responseSuccess(
                $response,
                Response::HTTP_OK,
                'Kiểm tra số điện thoại hợp lệ'
            );

        } catch (\Exception $e) {
            return $this->responseError(500, $e->getMessage());
        }
    }

    public function getCode(Request $request)
    {
        try {
            $data = $request->all();
            $data['ip'] = $request->ip();

            $response = $this->workerService->getCode($data);

            return $this->responseSuccess(
                $response,
                Response::HTTP_OK,
                'Kiểm tra và trả về code thành công'
            );

        } catch (\Exception $e) {
            return $this->responseError(500, $e->getMessage());
        }
    }

    public function startWorkerSession(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['ip'] = $request->ip();

            $response = $this->workerService->startWorkerSession($data);

            DB::commit();
            return $this->responseSuccess(
                $response,
                Response::HTTP_OK,
                'Kiểm tra code thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            return $this->responseError(500, $e->getMessage());
        }
    }
}
