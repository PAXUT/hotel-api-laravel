<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunSchedulerLocal extends Command
{
    protected $signature = 'scheduler:local';
    protected $description = 'Run the scheduler locally without cron';

    public function handle()
    {
        $this->info('Starting scheduler locally...');
        $this->info('Press Ctrl+C to stop');

        while (true) {
            // Chạy scheduler
            Artisan::call('schedule:run');
            
            // Hiển thị output nếu có
            $output = Artisan::output();
            if (!empty($output)) {
                $this->line($output);
            }

            // Đợi 1 phút trước khi chạy lại
            sleep(60);
        }
    }
} 