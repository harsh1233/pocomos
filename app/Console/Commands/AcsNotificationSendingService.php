<?php

namespace App\Console\Commands;

use App\Http\Controllers\Functions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AcsNotificationSendingService extends Command
{
    use Functions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AcsNotification:sendNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send ACS jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("AcsNotification:sendNotifications this cron is start for execute.");

        $notification = $this->getPendingNotification();


        foreach ($notification as $value) {
            $this->sendNotification($value);
        }

        Log::info("AcsNotification:sendNotifications this cron is successfully executed.");
    }
}
