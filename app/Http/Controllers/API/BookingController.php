<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function getdata(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'status' => 'error',
            ], 401);
        }

        $perPage = $request->get('perPage', 6);

        try {
            if ($user->role !== '1') {
                $booking = Booking::with('status')->where('user_id', $user->id)->paginate($perPage);
                return response()->json([
                    'message' => 'Lấy danh sách cho user thành công',
                    'data' => $booking,
                ], 200);
                if ($booking->isEmpty()) {
                    return response()->json([
                        'message' => 'Không có',
                        'data' => null,
                    ], 404);
                }
            }
            $query = Booking::with(['user', 'room'])
                ->orderBy('created_at', 'desc');

            if ($request->has('status_id')) {
                $query->where('status_id', $request->status_id);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $bookings = $query->paginate($perPage);

            $all = Booking::with(['user', 'room'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $bookings,
                'all' => $all,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get bookings error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cod(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401
            ], 401);
        }
        $booking = Booking::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'guest' => $request->guest,
            'total_price' => $request->total_price,
            'payment_method' => 'cod',
            'status_id' => '3',
            'payment_status' => 'unpaid',
        ]);
        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy booking', 'code' => 404], 404);
        }
        return response()->json(['message' => 'Đặt phòng thành công', 'status' => 200, 'data' => $booking]);
    }

    public function payWithVnpay(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập',
                'code' => 401
            ], 401);
        }

        try {
            DB::beginTransaction();

            $orderInfo = base64_encode(json_encode([
                'user_id' => $request->user_id,
                'room_id' => $request->room_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'guest' => $request->guest,
                'total_price' => $request->total_price,
            ]));

            $vnpData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => env('VNP_TMN_CODE'),
                "vnp_Amount" => intval($request->total_price) * 100,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => now()->format('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => $orderInfo,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => env('VNP_RETURN_URL'),
                "vnp_TxnRef" => uniqid(), // Tạo mã duy nhất
                "vnp_BankCode" => "VNBANK"
            ];

            ksort($vnpData);
            $query = '';
            $hashData = '';
            foreach ($vnpData as $key => $value) {
                $hashData .= $key . '=' . urlencode($value) . '&';
                $query .= urlencode($key) . '=' . urlencode($value) . '&';
            }
            $hashData = rtrim($hashData, '&');
            $query = rtrim($query, '&');

            $vnp_SecureHash = hash_hmac('sha512', $hashData, env('VNP_HASH_SECRET'));
            $paymentUrl = env('VNP_URL') . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

            DB::commit();
            return response()->json(['payUrl' => $paymentUrl]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Tạo thanh toán thất bại',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createBooking(Request $request, string $paymentMethod)
    {
        return Booking::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'guest' => $request->guest,
            'total_price' => $request->total_price,
            'payment_method' => $paymentMethod,
            'status_id' => '3',
            'payment_status' => 'unpaid',
        ]);
    }

    private function prepareVnpayData(Booking $booking)
    {
        return [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => env('VNP_TMN_CODE'),
            "vnp_Amount" => intval($booking->total_price) * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => 'Thanh toan dat phong ' . $booking->id,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => env('VNP_RETURN_URL'),
            "vnp_TxnRef" => $booking->id,
            "vnp_BankCode" => "VNBANK"
        ];
    }

    private function generateVnpayUrl(array $inputData)
    {
        $vnp_Url = env('VNP_URL');
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        ksort($inputData);

        $hashData = '';
        $query = '';
        foreach ($inputData as $key => $value) {
            $hashData .= $key . '=' . urlencode($value) . '&';
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $hashData = rtrim($hashData, '&');
        $query = rtrim($query, '&');

        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;
    }

    public function vnpayReturn(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputData = $this->getVnpayInputData($request);

            if (!$this->validateVnpaySignature($inputData)) {
                return $this->invalidSignatureResponse($inputData);
            }

            if ($inputData['vnp_ResponseCode'] !== '00') {
                return $this->failedPaymentResponse($inputData);
            }
            DB::commit();
            return $this->processSuccessfulPayment($inputData);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Xử lý thanh toán thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getVnpayInputData(Request $request)
    {
        $inputData = [];
        foreach ($request->query() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        return $inputData;
    }

    private function validateVnpaySignature(array $inputData)
    {
        if (empty($inputData['vnp_SecureHash'])) {
            return false;
        }

        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_SecureHash = $inputData['vnp_SecureHash'];

        // Remove hash and hash type from data before creating new hash
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        // Sort array by key
        ksort($inputData);

        // Create hash data string
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if (!empty($value)) {
                if ($i == 1) {
                    $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }

    private function processSuccessfulPayment(array $inputData)
    {
        $info = json_decode(base64_decode($inputData['vnp_OrderInfo']), true);

        if (!$info) {
            return response()->json(['status' => 'invalid_data'], 400);
        }

        $booking = Booking::create([
            'user_id' => $info['user_id'],
            'room_id' => $info['room_id'],
            'check_in_date' => $info['check_in_date'],
            'check_out_date' => $info['check_out_date'],
            'guest' => $info['guest'],
            'total_price' => $info['total_price'],
            'payment_method' => 'vnpay',
            'status_id' => 3,
            'payment_status' => 'paid',
            'payment_reference' => $inputData['vnp_TransactionNo'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Thanh toán VNPAY thành công!',
            'booking_id' => $booking->id,
            'amount' => (int)($inputData['vnp_Amount'] ?? 0) / 100,
            'room_id' => $booking->room_id,
            'room_name' => $booking->room->name ?? null,
            'check_in' => $booking->check_in_date,
            'check_out' => $booking->check_out_date
        ], 200);
    }

    private function failedPaymentResponse(array $inputData)
    {
        $inputData['vnp_TxnRef'];

        return response()->json([
            'status' => 'failed',
            'message' => 'Thanh toán VNPAY thất bại.',
            'vnp_ResponseCode' => $inputData['vnp_ResponseCode'] ?? null,
            'vnp_Message' => $inputData['vnp_Message'] ?? null,
            'vnp_TransactionNo' => $inputData['vnp_TransactionNo'] ?? null,
            'vnp_TxnRef' => $inputData['vnp_TxnRef'] ?? null,
        ], 400);
    }

    private function invalidSignatureResponse(array $inputData)
    {
        $inputData['vnp_TxnRef'];

        return response()->json([
            'status' => 'invalid_signature',
            'message' => 'Lỗi: Chữ ký không hợp lệ từ VNPAY. Giao dịch không an toàn.',
            'received_secure_hash' => $inputData['vnp_SecureHash'] ?? null,
        ], 400);
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $booking = Booking::with('room')->findOrFail($id);

            $validated = $request->validate([
                'status_id' => 'required|integer|in:1,2,3,4,5,6,7,8',
            ]);

            $booking->status_id = $validated['status_id'];
            $booking->save();

            $roomStatus = null;

            switch ($validated['status_id']) {
                // case 1:
                //     $roomStatus = 3;
                //     break;
                case 6:
                    $roomStatus = 2;
                    break;
                default:
                    $roomStatus = 1;
                    break;
            }
            if ($roomStatus && $booking->room) {
                $booking->room->update([
                    'status_room_id' => $roomStatus,
                ]);
            }
            if ($booking->status_id === 7) {
                $booking->review = 1;
                $booking->save();
            }
            if ($booking->payment_method === "vnpay" && ($booking->status_id === 2 || $booking->status_id === 4 || $booking->status_id === 5 )) {
                $booking->refund = "pending";
                $booking->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật trạng thái đơn hàng thành công',
                'data' => $booking->load('room'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Booking status update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật trạng thái đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $booking->payment_status = 'paid';
            $booking->save();

            return response()->json([
                'message' => 'Cập nhật thanh toán thành công',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cập nhật thanh toán thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRefund(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $booking->refund = 'refund';
            $booking->save();

            return response()->json([
                'message' => 'Cập nhật thành công',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cập nhật thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBookedDates($roomId)
    {
        try {
            $bookedDates = Booking::where('room_id', $roomId)
                ->where(function ($query) {
                    $query->where('status_id', 1) // Đã duyệt
                        // ->orWhere('status_id', 3) // Chờ duyệt
                        ->orWhere('status_id', 6) // Đã nhận phòng
                        ->orWhere('status_id', 8);
                })
                ->select('check_in_date', 'check_out_date')
                ->get()
                ->map(function ($booking) {
                    return [
                        'start' => $booking->check_in_date,
                        'end' => $booking->check_out_date
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $bookedDates
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi lấy danh sách ngày đã đặt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
