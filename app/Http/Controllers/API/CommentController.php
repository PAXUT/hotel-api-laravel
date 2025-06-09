<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index($id)
    {
        $review = Review::with(['user', 'room'])->where('room_id', $id)->get();
        // 
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        return response()->json([
            'message' => 'lấy danh sách đánh giá thành công',
            'code' => 200,
            'data' => $review,
        ]);
    }
    public function reviewInBooking($id)
    {
        $review = Review::with(['user', 'room'])->where('booking_id', $id)->first();
        // 
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        return response()->json([
            'message' => 'lấy danh sách đánh giá thành công',
            'code' => 200,
            'data' => $review,
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

        $userId = Auth::id();
        $bookingId = $request->booking_id;
        // Kiểm tra xem đã tồn tại đánh giá cho booking này chưa
        $existingReview = Review::where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Bạn đã đánh giá đơn đặt phòng này.',
                'code' => 400
            ], 400);
        }
        $review = Review::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'room_id' => $request->room_id,
            'content' => $request->content,
            'rating' => $request->rating,
        ]);
        Booking::where('id', $bookingId)->update([
            'review' => 2,
        ]);
        return response()->json([
            'message' => 'Thêm bình luận thành công',
            'code' => 200,
            'data' => $review,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
