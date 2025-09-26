<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove old task history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::transaction(function() {
            Task::with(['history' => fn($q) => $q->take(7)])->chunk(100, function($tasks) {
                foreach ($tasks as $task) {
                    $recentHistory = $task->history->pluck('id');
                    $task->history()->whereNotIn('id', $recentHistory)->delete();
                }
            });
        });
    }
}
