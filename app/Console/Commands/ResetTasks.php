<?php

namespace App\Console\Commands;

use App\Models\PunishmentHistory;
use App\Models\RewardHistory;
use \Recurr\Transformer\ArrayTransformer;
use \Recurr\Transformer\Constraint\BetweenConstraint;
use App\Models\Task;
use App\Models\TaskHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Recurr\Rule;

class ResetTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the count of all tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::transaction(function() {
            Task::chunk(100, function($tasks) {
                foreach ($tasks as $task) {
                    $dynamic = $task->dynamic()->first();
                    $time_zone = $dynamic->time_zone;

                    // only roll over tasks when it's midnight in your timezone, other wise skip
                    $currentTime = Carbon::now($time_zone);
                    $midnight = Carbon::today($time_zone);
                    $plus25 = $midnight->copy()->addMinutes(25);

                    // if (true) {
                    if ($currentTime->between($midnight, $plus25)) {
                        $transformer = new ArrayTransformer();
                        $startDateConstraint = Carbon::parse(now($time_zone)->subDay()->format('Y-m-d') . '23:59:59', $time_zone);
                        $endDateConstraint = Carbon::parse(now($time_zone)->addDay()->format('Y-m-d') . '00:00:00', $time_zone);
                        if ($task->end) {
                            $taskEndDateConstraint = Carbon::parse(Carbon::parse($task->end)->addDay()->format('Y-m-d') . '00:00:00', $time_zone);
                            if ($taskEndDateConstraint < $endDateConstraint) {
                                $endDateConstraint = $taskEndDateConstraint;
                            }
                        }
                        $startDate = Carbon::parse(Carbon::parse($task->start)->format('Y-m-d') . '00:00:00', $time_zone);

                        $rule = new Rule(substr($task->rrule, 6), $startDate);
                        $constraint = new BetweenConstraint($startDateConstraint, $endDateConstraint);
                        $isTaskDueToday = $transformer->transform($rule, $constraint)->count() > 0;

                        if ($isTaskDueToday) {
                            $user = $dynamic->submissives()->first();

                            if ($task->type == Task::TYPE_ENCOURAGE && $task->count < $task->target_count
                             || $task->type == Task::TYPE_DISCOURAGE && $task->count >= $task->target_count) {
                                if ($task->remove_points_on_failure && $user) {
                                    $user->update(['points' => $user->points - $task->value]);
                                }

                                if ($user) {
                                    $task->punishments()->get()->each(function ($punishment) use ($time_zone) {
                                        $punishment->update(['value' => $punishment->value + 1]);
                                        PunishmentHistory::Create(
                                            [
                                                'punishment_id' => $punishment->id,
                                                'date' => Carbon::now($time_zone)->format('Y-m-d'),
                                                'action' => PunishmentHistory::AUTO_ASSIGNED,
                                            ]
                                        );
                                    });
                                }
                            }

                            $wasComplete = $task->type == Task::TYPE_ENCOURAGE
                                ? $task->count >= $task->target_count
                                : $task->count < $task->target_count;

                            TaskHistory::updateOrCreate(
                                [
                                    'task_id' => $task->id,
                                    'date' => Carbon::now($time_zone)->subDay()->format('Y-m-d')
                                ],
                                [
                                    'was_complete' => $wasComplete,
                                    'count' => $task->count,
                                    'target_count' => $task->target_count,
                                ]
                            );

                            if ($task->type == Task::TYPE_DISCOURAGE && $user) {
                                $user->update(['points' => $user->points + $task->value]);

                                $task->rewards()->get()->each(function ($reward) use ($time_zone) {
                                    $reward->update(['bank' => $reward->bank + 1]);
                                    RewardHistory::Create(
                                        [
                                            'reward_id' => $reward->id,
                                            'date' => Carbon::now($time_zone)->format('Y-m-d'),
                                            'action' => RewardHistory::AUTO_GIVEN,
                                        ]
                                    );
                                });
                            }

                            $task->update(['count' => 0]);
                        }
                    }
                }
            });
        });
    }
}
