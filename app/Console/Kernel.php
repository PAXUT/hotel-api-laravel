<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CheckExpiredBookings::class,
        Commands\SchedulerControl::class,
        Commands\RunSchedulerLocal::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (File::exists(storage_path('framework/scheduler.lock'))) {
            return;
        }

        // Kiểm tra booking hết hạn
        $schedule->command('bookings:check-expired')->everyThirtyMinutes()->appendOutputTo(storage_path('logs/expired-bookings.log'));

        $schedule->command('room:update-status-before-checkin')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
