<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Events\RoomStatusUpdated;

class RoomController extends Controller
{
    public function getRoom(Request $request)
    {
        $perPage = $request->get('perPage');

        $rooms = Room::with('roomType', 'status', 'convenients', 'images')->paginate($perPage);
        $emptyrooms = Room::with('roomType', 'status', 'convenients', 'images')->where('status_room_id', '=', '1')->paginate($perPage);
        $all = Room::with('roomType', 'status', 'convenients', 'images')->get();
        // $roomforuser = Room::with('status', 'images')->get();

        if ($rooms->isEmpty()) {
            return response()->json([
                'message' => 'Không có phòng nào',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        return response()->json([
            'message' => 'Lấy danh sách phòng thành công',
            'code' => 200,
            'data' => $rooms,
            'all' => $all,
            'emptyrooms' => $emptyrooms,
        ], 200);
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
            'room_type_id' => 'required|exists:room_types,id',
            'price' => 'required|string|max:255',
            'description' => 'required|string',
            'status_id' => 'required|exists:statuses,id',

            'convenients' => 'required|array',
            'convenients.*' => 'exists:convenients,id',
            'image' => 'required|array',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            DB::beginTransaction(); // Bắt đầu transaction
            $room = Room::create([
                'name' => $request->name,
                'room_type_id' => $request->room_type_id,
                'price' => $request->price,
                'description' => $request->description,
                'status_room_id' => $request->status_id,
            ]);
            
            // Liên kết các tiện nghi
            if ($request->has('convenients')) {
                $room->convenients()->attach($request->convenients);
            }
            // Lưu trữ hình ảnh
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $file) {
                    $path = $file->store('rooms/' . $room->id, 'public'); // Lưu trong thư mục con theo room_id
                    RoomImage::create([ // Tạo bản ghi Image
                        'room_id' => $room->id,
                        'image_path' => Storage::url($path), // Lưu URL của ảnh
                    ]);
                }
            }
            event(new RoomStatusUpdated($room));
            DB::commit(); // Commit transaction
            return response()->json([
                'message' => 'Tạo phòng thành công',
                'code' => 201,
                'data' => $room->load('roomType', 'status', 'convenients', 'images'), // Load các relationship
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction nếu có lỗi
            return response()->json([
                'message' => 'Tạo phòng thất bại',
                'code' => 500,
                'error' => $e->getMessage(), // Trả về thông báo lỗi
            ], 500);
        }
    }

    public function show(string $id)
    {
        $room = Room::with('roomType', 'status', 'convenients', 'images')->find($id);
        event(new RoomStatusUpdated($room));
        if (!$room) {
            return response()->json([
                'message' => 'Không tìm thấy phòng',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        return response()->json([
            'message' => 'Thông tin phòng',
            'code' => 200,
            'data' => $room,
        ]);
    }

    public function update(Request $request, string $id)
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
        $room = Room::find($id);
        if (!$room) {
            return response()->json([
                'message' => 'Không tìm thấy phòng',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms')->ignore($room->id),
            ],
            'room_type_id' => 'required|exists:room_types,id',
            'price' => 'required|integer',
            'description' => 'required|string',
            'status_room_id' => 'required|exists:status_rooms,id',

            'convenients' => 'array',
            'convenients.*' => 'sometimes|exists:convenients,id',
            'image' => 'nullable|array',
            'image.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.unique' => 'Tên phòng đã tồn tại.',
            'price.integer' => 'Giá phòng phải là số nguyên.',
        ]);
        try {
            DB::beginTransaction();
            $room->update([
                'name' => $request->name,
                'room_type_id' => $request->room_type_id,
                'price' => $request->price,
                'description' => $request->description,
                'status_room_id' => $request->status_room_id,
            ]);
            if ($request->has('convenients')) {
                $room->convenients()->sync($request->convenients);
            }
            // 6. Xử lý xóa ảnh cũ (nếu có yêu cầu)
            if ($request->has('images_to_remove')) {
                $imagesToRemoveIds = $request->input('images_to_remove');
                // Chỉ xóa những ảnh thực sự thuộc về phòng này
                $imagesToDelete = RoomImage::where('room_id', $room->id)
                    ->whereIn('id', $imagesToRemoveIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    $filePath = str_replace('/storage/', '', $image->image_path);
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                    $image->delete();
                }
            }

            if ($request->has('remove_image_ids')) {
                foreach ($request->remove_image_ids as $imageId) {
                    $image = RoomImage::find($imageId);
                    if ($image) {
                        // Xóa file ảnh khỏi storage
                        Storage::delete($image->image_path);
                        $image->delete(); // Xóa khỏi DB
                    }
                }
            }

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $file) {
                    $path = $file->store("rooms/{$room->id}", 'public');
                    $room->images()->create([
                        'image_path' => Storage::url($path)
                    ]);
                }
            }
            event(new RoomStatusUpdated($room));

            DB::commit();
            return response()->json([
                'message' => 'Cập nhật phòng thành công',
                'code' => 200,
                'data' => $room->load('roomType', 'status', 'convenients', 'images'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Cập nhật phòng thất bại',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
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
        $room = Room::find($id);
        if (!$room) {
            return response()->json([
                'message' => 'Không tìm thấy phòng',
                'code' => 404,
                'data' => null,
            ], 404);
        }
        try {
            DB::beginTransaction(); // Bắt đầu transaction

            // Xóa tất cả hình ảnh liên quan đến phòng
            foreach ($room->images as $image) {
                $filePath = str_replace('/storage/', '', $image->image_path);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
                $image->delete();
            }

            $folderPath = 'rooms/' . $room->id;
            if (Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }

            $room->delete();
            event(new RoomStatusUpdated($room));

            DB::commit();

            return response()->json([
                'message' => 'Xóa phòng thành công',
                'code' => 200,
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction nếu có lỗi
            return response()->json([
                'message' => 'Xóa phòng thất bại',
                'code' => 500,
                'error' => $e->getMessage(), // Trả về thông báo lỗi
            ], 500);
        }
    }

    public function getAvailableRooms(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->get('per_page', 10);
        $guests = $request->input('guests');


        if (!$startDate || !$endDate) {
            return response()->json([
                'message' => 'Vui lòng chọn ngày đến và ngày đi',
                'code' => 400,
            ], 400);
        }
        // Lấy danh sách phòng đã bị đặt
        $bookedRoomIds = Booking::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('check_in_date', [$startDate, $endDate])
                ->orWhereBetween('check_out_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('check_in_date', '<=', $startDate)
                        ->where('check_out_date', '>=', $endDate);
                });
        })->pluck('room_id');

        $query = Room::with(['images', 'roomType', 'convenients', 'status'])
            ->whereNotIn('id', $bookedRoomIds)->where('status_room_id', 1);
        if ($guests) {
            $query->whereHas('roomType', function ($q) use ($guests) {
                $q->where('capacity', '>=', (int)$guests);
            });
        }

        $data = $query->paginate($perPage);

        $message = $data->isEmpty() ? 'Không tìm thấy phòng nào phù hợp' : 'Danh sách phòng còn trống';


        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }
}
