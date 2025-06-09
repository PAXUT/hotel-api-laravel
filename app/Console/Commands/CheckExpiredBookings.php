<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $currentDate = now();
            $expiredBookings = [];
            $Bookings = [];
            Log::info('Starting expired bookings check at: ' . $currentDate);

            // Case 1: Đã qua ngày check-out và vẫn đang chờ duyệt
            $case1Bookings = Booking::where('check_out_date', '<', $currentDate)
                ->where('status_id', 3)
                ->pluck('id')
                ->toArray();
            $expiredBookings = array_merge($expiredBookings, $case1Bookings);
            Log::info('Case 1 - Checkout passed bookings: ' . count($case1Bookings));

            // Case 2: Chưa thanh toán và đã đến ngày check-in (chỉ áp dụng cho COD)
            $case2Bookings = Booking::where(function ($query) use ($currentDate) {
                $query->whereDate('check_in_date', '<', $currentDate->toDateString())
                    ->orWhere(function ($q) use ($currentDate) {
                        $q->whereDate('check_in_date', $currentDate->toDateString())
                            ->whereTime('check_in_date', '<', '18:00:00');
                    });
            })
                ->where('payment_method', 'cod')
                ->where('payment_status', 'unpaid')
                ->where('status_id', 1)
                ->pluck('id')
                ->toArray();
            $expiredBookings = array_merge($expiredBookings, $case2Bookings);
            Log::info('Case 2 - Unpaid COD bookings: ' . count($case2Bookings));

            // Case 3: Đã qua 21:00 của ngày check-in nhưng chưa nhận phòng
            $case3Bookings = Booking::where(function ($query) use ($currentDate) {
                $query->whereDate('check_in_date', '<', $currentDate->toDateString())
                    ->orWhere(function ($q) use ($currentDate) {
                        $q->whereDate('check_in_date', $currentDate->toDateString())
                            ->whereTime('check_in_date', '<', '21:00:00');
                    });
            })
                ->where('status_id', 1)
                ->pluck('id')
                ->toArray();
            $expiredBookings = array_merge($expiredBookings, $case3Bookings);
            Log::info('Case 3 - Late check-in bookings (after 21:00): ' . count($case3Bookings));

            // đến hạn trả phòng mà chưa trả
            $check = Booking::where(function ($query) use ($currentDate) {
                $query->whereDate('check_out_date', '<', $currentDate->toDateString())
                    ->orWhere(function ($q) use ($currentDate) {
                        $q->whereDate('check_out_date', $currentDate->toDateString())
                            ->whereTime('check_out_date', '<', '8:00:00');
                    });
            })
                ->where('status_id', 6)
                ->pluck('id')
                ->toArray();
            $Bookings = array_merge($Bookings, $check);
            Log::info('Checkout bookings: ' . count($check));

            // Cập nhật trạng thái cho các booking
            if (!empty($expiredBookings)) {
                Booking::whereIn('id', $expiredBookings)
                    ->update(['status_id' => 5]); // 5 là trạng thái hết hạn

                $this->info('Updated ' . count($expiredBookings) . ' expired bookings');
                Log::info('Updated ' . count($expiredBookings) . ' expired bookings with IDs: ' . implode(', ', $expiredBookings));
            } else 
            if (!empty($Bookings)) {
                Booking::whereIn('id', $Bookings)
                    ->update(['status_id' => 8]); //trạng thái đến hạn trả phòng

                $this->info('Updated ' . count($Bookings) . ' expired bookings');
                Log::info('Updated ' . count($Bookings) . ' expired bookings with IDs: ' . implode(', ', $Bookings));
            }else {
                $this->info('No expired bookings found');
                Log::info('No expired bookings found at: ' . $currentDate);
            }
        } catch (\Exception $e) {
            $this->error('Error checking expired bookings: ' . $e->getMessage());
            Log::error('Error checking expired bookings: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
