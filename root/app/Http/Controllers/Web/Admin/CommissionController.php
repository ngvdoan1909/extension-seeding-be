<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Http\Resources\CommissionCollection;
use App\Http\Resources\CommissionResource;
use App\Services\CommissionService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    protected $commissionService;
    const PATH_VIEW = 'admin.pages.commissions.';

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function index()
    {
        try {
            $commissions = $this->commissionService->fetchCommissions();

            return view(self::PATH_VIEW . __FUNCTION__, compact('commissions'));
        } catch (\Exception $e) {
            dd($e->getMessage());
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
            return view(self::PATH_VIEW . __FUNCTION__, compact('commissions'));

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
            return view(self::PATH_VIEW . __FUNCTION__, compact('commissions'));

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
            return view(self::PATH_VIEW . __FUNCTION__, compact('commissions'));

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
            return view(self::PATH_VIEW . __FUNCTION__, compact('commissions'));

        }
    }
}
