<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user = User::GeterByEmail($request->email);
            if (!$user) {
                return response()->json(['code' => 400, 'message' => 'tên tài khoản ko tồn tại']);
            }
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['code' => 200, 'message' => 'Đăng nhập thành công', 'token' => $token, 'user' => $user,]);
        }

        return response()->json(['code' => 400, 'message' => 'Sai email hoặc mật khẩu']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|min:10|max:10',
        ], [
            'email.unique' => 'Email này đã được sử dụng, vui lòng chọn email khác.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'name.required' => 'Vui lòng nhập tên của bạn.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.min' => 'Số điện thoại phải có ít nhất 10 số.',
            'phone.max' => 'Số điện thoại không được quá 10 số.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        if ($user) {
            return response()->json([
                'message' => 'Đăng ký thành công',
                'code' => 200
            ]);
        } else {
            return response()->json([
                'message' => 'Đăng ký thất bại',
                'code' => 500
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'role' => $request->user()->role,
        ]);
    }

    public function getalluser(Request $request)
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
        $perPage = $request->get('perPage');

        $info = User::paginate($perPage);
        return response()->json([
            'data' => $info,
            'code' => 200,
        ]);
    }

    public function edit(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401
            ], 401);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:10',
                'min:10',
            ],
            // Bạn có thể thêm các trường khác cần chỉnh sửa ở đây, ví dụ email, password, v.v.
            // 'email' => [
            //     'required',
            //     'string',
            //     'email',
            //     'max:255',
            //     Rule::unique('users')->ignore($user->id),
            // ],
            // 'password' => [
            //     'nullable', // Cho phép không đổi mật khẩu
            //     'string',
            //     'min:8',
            //     'confirmed', // Yêu cầu trường password_confirmation
            // ],
        ], [
            'name.required' => 'Tên không được bỏ trống.',
            'name.string' => 'Tên phải là chuỗi ký tự.',
            'name.max' => 'Tên không được vượt quá :max ký tự.',
            'name.unique' => 'Tên người dùng đã tồn tại.',
            'phone.required' => 'Không được bỏ trống.',
            'phone.max' => 'Không được vượt quá :max ký tự.',
            // Thêm messages cho các trường khác nếu có
            // 'email.unique' => 'Email đã tồn tại.',
            // 'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            // 'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();

        // Trả về phản hồi thành công
        return response()->json([
            'message' => 'Lưu thay đổi thành công',
            'code' => 200,
            'data' => $user,
        ], 200);
    }
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401
            ], 401);
        }
        // Kiểm tra xác thực người dùng
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate dữ liệu
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', 'min:6',], // Password::defaults()
        ], [
            'current_password.required' => 'Mật khẩu không được bỏ trống.',
            'new_password.required' => 'Mật khẩu không được bỏ trống.',
            'new_password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return response()->json(['code' => 400, 'message' => 'Mật khẩu hiện tại không đúng']);
        }

        // Cập nhật mật khẩu
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Trả về response thành công
        return response()->json(['message' => 'Mật khẩu đã được cập nhật', 'code' => 200], 200);
    }
    public function updateStatus(Request $request, $id)
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
        try {
            $update = User::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|integer|in:0,1',
            ]);

            $update->status = $validated['status'];
            $update->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật trạng thái tài khoản công',
                'data' => $update,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật trạng thái tài khoản thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
