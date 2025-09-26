<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardRequest;
use App\Models\Reward;
use App\Models\RewardHistory;
use App\Models\Task;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    function all(Request $request) {
        $rewards = $request
            ->user()
            ->dynamics()
            ->first()
            ->rewards()
            ->with(['history', 'tasks' => fn($q) => $q->select('tasks.id')])
            ->get();

        return json_encode([
            'rewards' => $rewards,
        ]);
    }

    function create(RewardRequest $request) {
        try {
            Reward::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'name' => $request->name,
                'description' => $request->description,
                'value' => $request->value,
                'bank' => 0
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(RewardRequest $request, Reward $reward) {
        try {
            $reward->update([
                'name' => $request->name,
                'description' => $request->description,
                'value' => $request->value
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Reward $reward) {
        try {
            $reward->delete();
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function add(Request $request, Reward $reward) {
        try {
            $timeZone = $reward->dynamic()->first()->time_zone;

            if ($request->user()->isDom() || $request->user()->points >= $reward->value) {
                DB::transaction(function() use ($request, $reward, $timeZone) {
                    $reward->update(['bank' => $reward->bank + 1]);
                    $user = $request->user()->dynamics()->first()->submissives()->first();
                    if ($request->user()->isSub() && $user) {
                        $user->update(['points' => $user->points - $reward->value]);
                    }

                    RewardHistory::Create(
                        [
                            'reward_id' => $reward->id,
                            'date' => Carbon::now($timeZone)->format('Y-m-d'),
                            'action' => $request->user()->isSub() ? RewardHistory::BOUGHT : RewardHistory::GIVEN,
                        ]
                    );
                });

                return json_encode(['message' => 'success']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'failure']);
    }

    function remove(Request $request, Reward $reward) {
        try {
            $timeZone = $reward->dynamic()->first()->time_zone;

            if ($reward->bank > 0) {
                DB::transaction(function() use ($request, $reward, $timeZone) {
                    $reward->update(['bank' => $reward->bank - 1]);

                    RewardHistory::Create(
                        [
                            'reward_id' => $reward->id,
                            'date' => Carbon::now($timeZone)->format('Y-m-d'),
                            'action' => $request->user()->isSub() ? RewardHistory::USED : RewardHistory::TAKEN,
                        ]
                    );
                });

                return json_encode(['message' => 'success']);
            } else {
                return json_encode(['message' => 'failure: count at 0.']);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function points(Request $request) {
        try {
            $points = $request
                ->user()
                ->dynamics()
                ->first()
                ->submissives()
                ->first()
                ->points;
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['points' => $points]);
    }

    function addPoint(Request $request) {
        try {
            $user = $request
                ->user()
                ->dynamics()
                ->first()
                ->submissives()
                ->first();

            $user->update(['points' => $user->points + 1]);
            $user->refresh();
            $points = $user->points;
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['points' => $points]);
    }

    function removePoint(Request $request) {
        try {
            $user = $request
                ->user()
                ->dynamics()
                ->first()
                ->submissives()
                ->first();

            $user->update(['points' => $user->points - 1]);
            $user->refresh();
            $points = $user->points;
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['points' => $points]);
    }

    function BankRewardCount(Request $request) {
        try {
            $rewards = $request
                ->user()
                ->dynamics()
                ->first()
                ->rewards()
                ->sum("bank");

            return json_encode([
                'count' => strval($rewards),
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function assign(Request $request, Reward $reward, Task $task) {
        try {
            $task->rewards()->attach($reward->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function unassign(Request $request, Reward $reward, Task $task) {
        try {
            $task->rewards()->detach($reward->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
