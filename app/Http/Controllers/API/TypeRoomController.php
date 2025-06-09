<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Room_types;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypeRoomController extends Controller
{
    public function getdata()
    {
        $typeRoom = Room_types::all();
        return response()->json([
            'message' => 'Lấy dữ liệu thành công',
            'code' => 200,
            'data' => $typeRoom,
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
            'capacity' => 'required|string|max:255',
            'des' => 'required|string|max:255',
        ]);
        $typeRoom = Room_types::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'description' => $request->des,
        ]);
        return response()->json([
            'message' => 'Thêm loại phòng thành công',
            'code' => 200,
            'data' => $typeRoom,
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
        $typeRoom = Room_types::find($id);
        if (!$typeRoom) {
            return response()->json([
                'message' => 'Không tìm thấy loại phòng',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        $typeRoom->delete();
        return response()->json([
            'message' => 'Xóa loại phòng thành công',
            'code' => 200,
            'data' => null,
        ]);
    }
}
