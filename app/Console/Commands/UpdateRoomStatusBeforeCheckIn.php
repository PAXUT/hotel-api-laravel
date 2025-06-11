<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRoomStatusBeforeCheckIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room:update-status-before-checkin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật trạng thái phòng trước ngày khách đến';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->toDateString();

        // Lấy các booking có ngày check-in là hôm nay
        $bookings = Booking::whereDate('check_in_date', $today)->get();

        foreach ($bookings as $booking) {
            if ($booking->status_id === 1) {
                $room = $booking->room;
                if ($room) {
                    $room->status_room_id = 3; // Cập nhật trạng thái phòng thành 3="khách đã đặt"
                    $room->save();
                    $this->info("Updated room {$room->id} to status 3 for check-in date {$booking->check_in_date}.");
                }
            }else if ($booking->status_id === 5) {
                $room = $booking->room;
                if ($room) {
                    $room->status_room_id = 1;// Cập nhật trạng thái phòng thành 1="phòng trống"
                    $room->save();
                    $this->info("Updated room {$room->id} to status 3 for check-in date {$booking->check_in_date}.");
                }
            }
        }

        return 0;
    }
}
