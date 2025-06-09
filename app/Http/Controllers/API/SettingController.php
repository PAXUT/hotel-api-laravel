<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        
        // Thêm đường dẫn đầy đủ cho ảnh
        if (isset($settings['logo_url'])) {
            $settings['logo_url'] = asset($settings['logo_url']);
        }
        
        // Xử lý banner urls
        $bannerUrls = [];
        for ($i = 1; $i <= 6; $i++) {
            $bannerKey = "banner_{$i}";
            if (isset($settings[$bannerKey])) {
                $bannerUrls[] = [
                    'preview' => asset($settings[$bannerKey]),
                    'key' => $bannerKey
                ];
            }
        }
        $settings['banners'] = $bannerUrls;

        return response()->json($settings);
    }

    public function update(Request $request)
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

        // Xử lý upload logo
        if ($request->hasFile('logo')) {
            // Xóa logo cũ nếu có
            $this->deleteImage('logo_url');
            
            $logo = $request->file('logo');
            $logoPath = $logo->store('public/logos');
            $logoUrl = Storage::url($logoPath);
            Setting::updateOrCreate(
                ['key' => 'logo_url'],
                ['value' => $logoUrl]
            );
        }

        // Xử lý upload banner
        for ($i = 1; $i <= 6; $i++) {
            $bannerKey = "banner_{$i}";
            if ($request->hasFile($bannerKey)) {
                // Xóa banner cũ nếu có
                $this->deleteImage($bannerKey);
                
                $banner = $request->file($bannerKey);
                $bannerPath = $banner->store('public/banners');
                $bannerUrl = Storage::url($bannerPath);
                Setting::updateOrCreate(
                    ['key' => $bannerKey],
                    ['value' => $bannerUrl]
                );
            }
        }

        // Xử lý các setting khác
        $textFields = ['site_name', 'address', 'phone', 'introduce', 'slogan','facebook', 'email'];
        foreach ($textFields as $field) {
            if ($request->has($field)) {
                Setting::updateOrCreate(
                    ['key' => $field],
                    ['value' => $request->input($field)]
                );
            }
        }

        return response()->json([
            'message' => 'Cập nhật thành công',
            'code' => 200
        ]);
    }

    public function deleteImage($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                // Lấy đường dẫn file từ URL
                $path = str_replace('/storage', 'public', $setting->value);
                
                // Xóa file từ storage
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
                
                // Xóa record từ database
                $setting->delete();
            }
            
            return response()->json([
                'message' => 'Xóa ảnh thành công',
                'code' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa ảnh',
                'error' => $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function deleteBanner(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== '1') {
            return response()->json([
                'message' => 'Không có quyền thực hiện',
                'code' => 403
            ], 403);
        }

        $key = $request->input('key');
        if (!$key || !str_starts_with($key, 'banner_')) {
            return response()->json([
                'message' => 'Key không hợp lệ',
                'code' => 400
            ], 400);
        }

        return $this->deleteImage($key);
    }

    public function deleteLogo()
    {
        $user = Auth::user();
        if (!$user || $user->role !== '1') {
            return response()->json([
                'message' => 'Không có quyền thực hiện',
                'code' => 403
            ], 403);
        }

        return $this->deleteImage('logo_url');
    }
}
