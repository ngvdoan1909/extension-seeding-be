<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Deposit;
use App\Models\WithDraw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    protected $user;
    const PATH_VIEW = 'admin.pages.users.';
    const POINT_RATE = 10;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index()
    {
        $data = $this->user->select('id', 'user_id', 'name', 'email', 'role')->latest('id')->get();

        return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
    }

    public function create()
    {
        return view(self::PATH_VIEW . __FUNCTION__);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:1,2'
        ]);

        try {
            $this->user->create([
                'user_id' => \Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Thêm người dùng thành công!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Lỗi khi thêm người dùng: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = $this->user->where('user_id', $id)->first();
        return view(self::PATH_VIEW . __FUNCTION__, compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = $this->user->where('user_id', $id)->first();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:1,2'
        ]);

        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role
            ];

            if ($request->password) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return redirect()->route('admin.users.index')
                ->with('success', 'Cập nhật người dùng thành công!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->user->where('user_id', $id)->first();
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'Xóa người dùng thành công!');
        } catch (\Exception $e) {
            dd($e->getMessage());
            return back()->withInput()
                ->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }
}