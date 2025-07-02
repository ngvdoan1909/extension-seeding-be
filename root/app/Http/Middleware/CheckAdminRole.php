<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 1) {
            abort(403);
            // return redirect()->route('admin.login')->withErrors([
            //     'email' => 'Bạn không có quyền truy cập trang này.'
            // ]);
        }

        return $next($request);
    }
}