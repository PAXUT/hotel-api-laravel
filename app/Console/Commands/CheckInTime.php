<?php

namespace App\Console\Commands;

use App\Events\BookingStatusUpdated;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-in-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check check_in_time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDate = now();
        $expiredBookings = [];

        $Bookings = Booking::where(function ($query) use ($currentDate) {
            $query->whereDate('check_in_date', '<', $currentDate->toDateString())
                ->orWhere(function ($q) use ($currentDate) {
                    $q->whereDate('check_in_date', $currentDate->toDateString())
                        ->whereTime('check_in_date', '<', '14:00:00');
                });
        })
            ->where('status_id', 1)
            ->pluck('id')
            ->toArray();
        $expiredBookings = array_merge($expiredBookings, $Bookings);
        Log::info('Case 3 - Late check-in bookings (after 14:00): ' . count($Bookings));
        // Cập nhật trạng thái cho các booking
        if (!empty($expiredBookings)) {
            foreach ($expiredBookings as $id) {
                $booking = Booking::find($id);
                if ($booking) {
                    $booking->status_id = 5;// 5 là trạng thái phòng hết hạn
                    $booking->save();
                    event(new BookingStatusUpdated($booking));
                }
            }
            $this->info('Updated ' . count($expiredBookings) . ' expired bookings');
            Log::info('Updated ' . count($expiredBookings) . ' expired bookings with IDs: ' . implode(', ', $expiredBookings)); 
        } else {
            $this->info('No expired bookings found');
            Log::info('No expired bookings found at: ' . $currentDate);
        }
    }
}
