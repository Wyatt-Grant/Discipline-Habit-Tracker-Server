<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\SendPushNotification;

class testPushNotification extends Command
{
    use SendPushNotification;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:test-push-notification';
    protected $signature = 'app:push-notification {APN : The APN} {device : The device}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->pushNotifications("Test Push Title", "Test Push SubTitle", "Test Push Body", $this->argument('APN'), $this->argument('device'));
    }
}
