<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function responseSuccess($data, int $statusCode, string $messages): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => $statusCode,
            'message' => $messages,
            'data' => $data
        ];

        return response()->json($response, $statusCode);
    }

    public function responseError(int $statusCode, string $messages): JsonResponse
    {
        $response = [
            'success' => false,
            'code' => $statusCode,
            'errors' => [
                'error_message' => $messages,
            ],
        ];

        return response()->json($response, $statusCode);
    }
}
