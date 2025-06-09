<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Convenient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConvenientController extends Controller
{
    public function get()
    {
        $conv = Convenient::all();
        if ($conv->isEmpty()) {
            return response()->json([
                'message' => 'Không có tiện ích nào',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        return response()->json([
            'message' => 'Danh sách tiện ích',
            'code' => 200,
            'data' => $conv,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401,
                'data' => null,
            ], 401);
        }
        if ($user->role !== '1') {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này',
                'code' => 403,
                'data' => null, 
            ], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required',
        ]);
        $conv = Convenient::create([
            'name_convenient' => $request->name,
            'icon' => $request->icon,
        ]);
        return response()->json([
            'message' => 'Thêm tiện ích thành công',
            'code' => 201,
            'data' => $conv,
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401,
                'data' => null,
            ], 401);
        }
        if ($user->role !== '1') {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này',
                'code' => 403,
                'data' => null, 
            ], 403);
        }
        $conv = Convenient::find($id);
        if (!$conv) {
            return response()->json([
                'message' => 'Không tìm thấy tiện ích',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        $conv->delete();
        return response()->json([
            'message' => 'Xóa tiện ích thành công',
            'code' => 200,
            'data' => null,
        ]);
    }
}
