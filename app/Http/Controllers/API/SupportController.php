<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class SupportController extends Controller
{
    public function getData()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401,
                'data' => null,
            ], 401);
        }

        $sup = Support::with('user')->get();

        return response()->json([
            'message' => 'Thành công',
            'code' => 200,
            'data' => $sup,
        ], 200);
    }

    public function getDataByUser()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401,
                'data' => null,
            ], 401);
        }

        $sup = Support::with('user')->where('user_id', $user->id)->get();

        return response()->json([
            'message' => 'Thành công',
            'code' => 200,
            'data' => $sup,
        ], 200);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|min:10|max:10',
            'message' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $accessToken = $request->bearerToken();
            $userId = null;
            if ($accessToken) {
                $token = PersonalAccessToken::findToken($accessToken);
                if ($token) {
                    $userId = $token->tokenable_id;
                }
            }

            $supports = Support::create([
                'user_id' => $userId,
                'name' =>  $request->name,
                'email' =>  $request->email,
                'phone' =>  $request->phone,
                'message' =>  $request->message,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Yêu cầu hỗ trợ thành công',
                'code' => 201,
                'data' => $supports->load('user',), // Load các relationship
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction nếu có lỗi
            return response()->json([
                'message' => 'Yêu cầu hỗ trợ thất bại',
                'code' => 500,
                'error' => $e->getMessage(), // Trả về thông báo lỗi
            ], 500);
        }
    }
    public function feedback(Request $request, string $id)
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
        $sup = Support::find($id);

        $request->validate([
            'text' => 'required|string|max:255',
        ]);

        $sup->response = $request->text;
        $sup->status = 'processing';
        $sup->responded_at = Carbon::now();
        $sup->save();

        return response()->json([
            'message' => 'Phản hồi thành công',
            'code' => 200,
            'data' => $sup,
        ], 200);
    }
}
