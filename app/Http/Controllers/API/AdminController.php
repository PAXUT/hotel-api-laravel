<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Kiểm tra xem người dùng có quyền admin hay không
        if ($request->user()->role != 1) {
            return response()->json(['message' => 'Bạn không có quyền truy cập!'], 403);
        }

        // Trả về thông tin người dùng
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role // Đảm bảo trả về role
        ]);
    }
}
