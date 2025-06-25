<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|max:255|min:1',
        ]);

        $user = User::create([
            'user_id' => \Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Hash::make($data['password']),
        ]);

        return $this->responseSuccess(
            [
                'name' => $user['name'],
                'email' => $user['email'],
            ],
            Response::HTTP_CREATED,
            'Đăng ký thành công'
        );
    }

    public function login()
    {
        $data = request(['email', 'password']);

        if (!$token = auth()->attempt($data)) {
            return $this->responseSuccess(
                null,
                Response::HTTP_UNAUTHORIZED,
                'Thông tin đăng nhập không đúng'
            );
        }

        return $this->respondWithToken($token);
    }

    public function verifyToken()
    {
        try {
            $user = auth()->user();
            if (!$user) {

                return $this->responseSuccess(
                    [
                        'data' => false
                    ],
                    Response::HTTP_UNAUTHORIZED,
                    'Error'
                );
            }

            return $this->responseSuccess(
                [
                    'data' => true
                ],
                Response::HTTP_OK,
                'Keng'
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token lỗi'], 401);
        }
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 666
        ]);
    }
}
