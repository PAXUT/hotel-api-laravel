<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SchedulerControl extends Command
{
    protected $signature = 'scheduler:control {action : Action to perform (start/stop)}';
    protected $description = 'Control the Laravel scheduler';

    public function handle()
    {
        $action = $this->argument('action');
        $lockFile = storage_path('framework/scheduler.lock');

        switch ($action) {
            case 'start':
                if (File::exists($lockFile)) {
                    File::delete($lockFile);
                }
                $this->info('Scheduler has been started');
                break;

            case 'stop':
                if (!File::exists($lockFile)) {
                    File::put($lockFile, '');
                }
                $this->info('Scheduler has been stopped');
                break;

            default:
                $this->error('Invalid action. Use start or stop');
                return 1;
        }

        return 0;
    }
} 