<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Group;
use App\Models\RewardHistory;
use \Recurr\Rule;
use \Recurr\Transformer\TextTransformer;
use \Recurr\Transformer\ArrayTransformer;
use \Recurr\Transformer\Constraint\BetweenConstraint;
use App\Models\Task;
use App\Models\TaskHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\SendPushNotification;
use Exception;

class TaskController extends Controller
{
    use SendPushNotification;

    function all(Request $request) {
        $tasks = $request
            ->user()
            ->dynamics()
            ->first()
            ->tasks()
            ->select('tasks.*')
            ->leftJoin('groups', 'tasks.group_id', '=', 'groups.id')
            ->with(['history' => fn($q) => $q->take(6), 'group'])
            ->orderBy('tasks.count', 'DESC')
            ->orderBy('groups.sort_order', 'ASC')
            ->orderBy('tasks.name')
            ->orderBy('tasks.id')
            ->get();

        $time_zone = $request->user()->dynamics()->first()->time_zone;

        $textTransformer = new TextTransformer();
        $transformer = new ArrayTransformer();

        $tasks = $tasks->map(function ($task) use ($textTransformer, $transformer, $time_zone) {
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
            $task->is_task_due_today = $isTaskDueToday ? 1 : 0;
            $task->rule_text = $textTransformer->transform($rule);
            $task->time_zone = $time_zone;

            $task->color = $task->group?->color;

            return $task;
        });

        return json_encode([
            'tasks' => $tasks,
        ]);
    }

    function create(TaskRequest $request) {
        try {
            Task::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type ?? Task::TYPE_ENCOURAGE,
                'value' => $request->value,
                'count' => 0,
                'target_count' => $request->target_count,
                'max_count' => $request->max_count,
                'rrule' => $request->rrule,
                'start' => $request->start,
                'end' => $request->end,
                'remove_points_on_failure' => $request->remove_points_on_failure,
                'remind' => $request->remind,
                'remind_time' => $request->remind_time,
                'restrict' => $request->restrict,
                'restrict_before' => $request->restrict_before,
                'restrict_time' => $request->restrict_time,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(TaskRequest $request, Task $task) {
        try {
            $task->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type ?? Task::TYPE_ENCOURAGE,
                'value' => $request->value,
                'count' => $task->count <= $task->max_count ? $task->count : $task->max_count,
                'target_count' => $request->target_count,
                'max_count' => $request->max_count,
                'rrule' => $request->rrule,
                'start' => $request->start,
                'end' => $request->end,
                'remove_points_on_failure' => $request->remove_points_on_failure,
                'remind' => $request->remind,
                'remind_time' => $request->remind_time,
                'restrict' => $request->restrict,
                'restrict_before' => $request->restrict_before,
                'restrict_time' => $request->restrict_time,
            ]);

            if ($request->group_id) {
                $task->update([
                    'group_id' => $request->group_id,
                ]);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Task $task) {
        try {
            DB::transaction(function() use ($request, $task) {
                $task->messages()->detach();
                $task->delete();
            });
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function complete(Request $request, Task $task) {
        try {
            $dynamic = $task->dynamic()->first();
            $timeZone = $task->dynamic()->first()->time_zone;

            if ($task->count < $task->max_count) {
                DB::transaction(function() use ($request, $task, $timeZone, $dynamic) {
                    $task->update(['count' => $task->count + 1]);
                    $task->refresh();
                    if ($task->count == $task->target_count) {
                        $user = $request->user()->dynamics()->first()->submissives()->first();
                        if ($user) {
                            if ($task->type == Task::TYPE_ENCOURAGE) {
                                $user->update(['points' => $user->points + $task->value]);

                                $task->rewards()->get()->each(function ($reward) use ($timeZone) {
                                    $reward->update(['bank' => $reward->bank + 1]);
                                    RewardHistory::Create(
                                        [
                                            'reward_id' => $reward->id,
                                            'date' => Carbon::now($timeZone)->format('Y-m-d'),
                                            'action' => RewardHistory::AUTO_GIVEN,
                                        ]
                                    );
                                });
                            } else {
                                $user->update(['points' => $user->points - $task->value]);

                                $task->rewards()->get()->each(function ($reward) use ($timeZone) {
                                    if ($reward->bank > 0) {
                                        $reward->update(['bank' => $reward->bank - 1]);
                                        RewardHistory::Create(
                                            [
                                                'reward_id' => $reward->id,
                                                'date' => Carbon::now($timeZone)->format('Y-m-d'),
                                                'action' => RewardHistory::AUTO_TAKEN,
                                            ]
                                        );
                                    }
                                });
                            }
                        }
                    }
                });

                $message = $task->messages()->inRandomOrder()->first();

                $sub = $request->user()->dynamics()->first()->submissives()->first();
                $dom = $request->user()->dynamics()->first()->dominants()->first();
                $completedBy = $request->user()->id == $dom?->id ? $dom?->name : $sub?->name;
                $notifyAPN = $request->user()->id == $dom?->id ? $sub?->APN : $dom?->APN;
                $notifyDevice = $request->user()->id == $dom?->id ? $sub?->device : $dom?->device;
                $this->pushNotifications(
                    $task->name,
                    "($task->count/$task->target_count)",
                    "completed by $completedBy",
                    $notifyAPN,
                    $notifyDevice
                );

                if ($message) {
                    return json_encode([
                        'message' => $message->name . "\n\n" .  $message->description,
                        'emojis' => $dynamic->default_reward_emojis
                    ]);
                } else {
                    return json_encode(['message' => 'NONE']);
                }
            } else {
                return json_encode(['message' => 'NONE']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function uncomplete(Request $request, Task $task) {
        try {
            $timeZone = $task->dynamic()->first()->time_zone;

            if ($task->count > 0) {
                DB::transaction(function() use ($request, $task, $timeZone) {
                    if ($task->count == $task->target_count) {
                        $user = $request->user()->dynamics()->first()->submissives()->first();
                        if ($user) {
                            if ($task->type == Task::TYPE_ENCOURAGE) {
                                $user->update(['points' => $user->points - $task->value]);

                                $task->rewards()->get()->each(function ($reward) use ($timeZone) {
                                    if ($reward->bank > 0) {
                                        $reward->update(['bank' => $reward->bank - 1]);
                                        RewardHistory::Create(
                                            [
                                                'reward_id' => $reward->id,
                                                'date' => Carbon::now($timeZone)->format('Y-m-d'),
                                                'action' => RewardHistory::AUTO_TAKEN,
                                            ]
                                        );
                                    }
                                });
                            } else {
                                $user->update(['points' => $user->points + $task->value]);

                                $task->rewards()->get()->each(function ($reward) use ($timeZone) {
                                    $reward->update(['bank' => $reward->bank + 1]);
                                    RewardHistory::Create(
                                        [
                                            'reward_id' => $reward->id,
                                            'date' => Carbon::now($timeZone)->format('Y-m-d'),
                                            'action' => RewardHistory::AUTO_GIVEN,
                                        ]
                                    );
                                });
                            }
                        }
                    }
                    $task->update(['count' => $task->count - 1]);
                    $task->refresh();
                });

                $sub = $request->user()->dynamics()->first()->submissives()->first();
                $dom = $request->user()->dynamics()->first()->dominants()->first();
                $completedBy = $request->user()->id == $dom?->id ? $dom?->name : $sub?->name;
                $notifyAPN = $request->user()->id == $dom?->id ? $sub?->APN : $dom?->APN;
                $notifyDevice = $request->user()->id == $dom?->id ? $sub?->device : $dom?->device;
                $this->pushNotifications(
                    $task->name,
                    "($task->count/$task->target_count)",
                    "reverted by $completedBy",
                    $notifyAPN,
                    $notifyDevice
                );

                return json_encode(['message' => 'success']);
            } else {
                return json_encode(['message' => 'failure: already at min count.']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function dailyRemainingCount(Request $request) {
        try {
            $tasks = $request
                ->user()
                ->dynamics()
                ->first()
                ->tasks()
                ->whereRaw('((tasks.type = 1 AND tasks.count < tasks.target_count) OR (tasks.type = 2 AND tasks.count >= tasks.target_count))')
                ->get();

            $time_zone = $request->user()->dynamics()->first()->time_zone;

            $transformer = new ArrayTransformer();

            $tasks = $tasks->filter(function ($task) use ($transformer, $time_zone) {
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

                return $transformer->transform($rule, $constraint)->count() > 0;
            });

            return json_encode([
                'count' => strval($tasks->count()),
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function completeHistory(Request $request, TaskHistory $taskHistory) {
        try {
            if ($taskHistory->count < $taskHistory->target_count) {
                $taskHistory->update([
                    'count' => $taskHistory->count + 1,
                    'was_complete' => ($taskHistory->count + 1) >= $taskHistory->target_count
                ]);

                return json_encode(['message' => 'success']);
            } else {
                return json_encode(['message' => 'failure: already at target count.']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function uncompleteHistory(Request $request, TaskHistory $taskHistory) {
        try {
            if ($taskHistory->count > 0) {
                $taskHistory->update([
                    'count' => $taskHistory->count - 1,
                    'was_complete' => ($taskHistory->count - 1) >= $taskHistory->target_count
                ]);

                return json_encode(['message' => 'success']);
            } else {
                return json_encode(['message' => 'failure: already at min count.']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function assignGroup(Request $request, Task $task, Group $group) {
        try {
            $task->update([
                'group_id' => $group->id
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function unassignGroup(Request $request, Task $task, Group $group) {
        try {
            $task->update([
                'group_id' => null
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function reminders(Request $request) {
        try {
            $tasks = $request
                ->user()
                ->dynamics()
                ->first()
                ->tasks()
                ->where('tasks.remind', 1)
                ->get();

            $time_zone = $request->user()->dynamics()->first()->time_zone;

            $transformer = new ArrayTransformer();

            $reminders = collect();
            $tasks->each(function ($task) use ($transformer, $time_zone, $reminders) {
                if ($task->type == Task::TYPE_ENCOURAGE && $task->count >= $task->target_count) {
                    return true;
                }
                if ($task->type == Task::TYPE_DISCOURAGE && $task->count < $task->target_count) {
                    return true;
                }

                $startDateConstraint = Carbon::parse(now($time_zone)->subDay()->format('Y-m-d') . '23:59:59', $time_zone);
                $endDateConstraint = Carbon::parse(now($time_zone)->addDays(2)->format('Y-m-d') . '00:00:00', $time_zone);
                if ($task->end) {
                    $taskEndDateConstraint = Carbon::parse(Carbon::parse($task->end)->addDay()->format('Y-m-d') . '00:00:00', $time_zone);
                    if ($taskEndDateConstraint < $endDateConstraint) {
                        $endDateConstraint = $taskEndDateConstraint;
                    }
                }
                $startDate = Carbon::parse(Carbon::parse($task->start)->format('Y-m-d') . '00:00:00', $time_zone);

                $rule = new Rule(substr($task->rrule, 6), $startDate);
                $constraint = new BetweenConstraint($startDateConstraint, $endDateConstraint);
                $occerances = $transformer->transform($rule, $constraint);

                foreach ($occerances as $occerance) {
                    $date_time = $occerance->getStart()->format('Y-m-d ') . $task->remind_time;

                    $reminder = [];
                    $reminder['id'] = $occerance->getStart()->format('Y-m-d ') . $task->remind_time . ' - ' . $task->id;
                    $reminder['date_time'] = $date_time;
                    $reminder['title'] = $task->name;
                    $reminder['description'] = $task->description;
                    $reminder['count'] = $task->count . "/" . $task->target_count;
                    $reminder['time_zone'] = $time_zone;
                    $reminders->push($reminder);
                }
            });

            return json_encode([
                'reminders' => $reminders,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }
}
