<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Http\Resources\CommissionCollection;
use App\Http\Resources\CommissionResource;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function index()
    {
        try {
            $commissions = $this->commissionService->fetchCommissions();

            return $this->responseSuccess(
                new CommissionCollection($commissions),
                Response::HTTP_OK,
                'Lấy danh sách nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $commission = $this->commissionService->getDetailCommission($id);

            return $this->responseSuccess(
                new CommissionResource($commission),
                Response::HTTP_OK,
                'Lấy chi tiết nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(CommissionRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $commission = $this->commissionService->createNewCommission($data);

            DB::commit();

            return $this->responseSuccess(
                [],
                Response::HTTP_CREATED,
                'Thêm mới nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function update(CommissionRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $commission = $this->commissionService->updateCommission($id, $data);

            DB::commit();

            return $this->responseSuccess(
                [],
                Response::HTTP_OK,
                'Cập nhật nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $commission = $this->commissionService->deleteCommission($id);

            DB::commit();

            return $this->responseSuccess(
                [],
                Response::HTTP_OK,
                'Xóa nhiệm vụ thành công'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
