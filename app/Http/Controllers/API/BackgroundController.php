<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Background;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BackgroundController extends Controller
{
    public function list()
    {
        $back = Background::all();
        if ($back->isEmpty()) {
            return response()->json([
                'message' => 'Không có background nào',
                'code' => 404,
                'data' => [],
            ], 404);
        }
        $normalizedBackgrounds = $back->map(function ($bg) {
            return [
                'id' => $bg->id,
                'path' => asset($bg->path),
                'created_at' => $bg->created_at,
                'updated_at' => $bg->updated_at,
            ];
        });
        return response()->json([
            'message' => 'Danh sách background',
            'code' => 200,
            'data' => $normalizedBackgrounds,
        ]);
    }

    public function addBackground(Request $request)
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

        $validator = Validator::make($request->all(), [
            'backgroundimage' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Lỗi validation',
                'code' => 422,
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        if ($request->hasFile('backgroundimage')) {
            $path = $request->file('backgroundimage')->store('backgrounds', 'public');

            $back = Background::create([
                'path' => $path,
            ]);

            $normalizedBackground = [
                'id' => $back->id,
                'path' => asset($back->path),
                'created_at' => $back->created_at,
                'updated_at' => $back->updated_at,
            ];

            return response()->json([
                'message' => 'Thêm ảnh bìa thành công',
                'code' => 201,
                'data' => $normalizedBackground,
            ], 201);
        }

        return response()->json([
            'message' => 'Không tìm thấy file ảnh',
            'code' => 400,
            'data' => null,
        ], 400);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401
            ], 401);
        }
        if($user->role !== '1'){
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện hành động này',
                'code' => 403
            ], 403);
        }
        $background = Background::find($id);
        if (!$background) {
            return response()->json(['message' => 'Không tìm thấy ảnh', 'code' => 404], 404);
        }
        // Đảm bảo xóa file trong storage/public
        $filePath = "public/" . $background->path;
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }
        $background->delete();

        return response()->json(['message' => 'Xóa ảnh thành công', 'code' => 200]);
    }
}
