<?php

namespace App\Http\Controllers;

use App\Http\Requests\PunishmentRequest;
use App\Models\Punishment;
use App\Models\PunishmentHistory;
use App\Models\Task;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PunishmentController extends Controller
{
    function all(Request $request) {
        $punishments = $request
            ->user()
            ->dynamics()
            ->first()
            ->punishments()
            ->with(['history', 'tasks' => fn($q) => $q->select('tasks.id')])
            ->get();

        return json_encode([
            'punishments' => $punishments,
        ]);
    }

    function create(PunishmentRequest $request) {
        try {
            Punishment::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'name' => $request->name,
                'description' => $request->description,
                'value' => 0,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(PunishmentRequest $request, Punishment $punishment) {
        try {
            $punishment->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Punishment $punishment) {
        try {
            $punishment->delete();
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function add(Request $request, Punishment $punishment) {
        try {
            $timeZone = $punishment->dynamic()->first()->time_zone;

            DB::transaction(function() use ($request, $punishment, $timeZone) {
                $punishment->update(['value' => $punishment->value + 1]);
                PunishmentHistory::Create(
                    [
                        'punishment_id' => $punishment->id,
                        'date' => Carbon::now($timeZone)->format('Y-m-d'),
                        'action' => PunishmentHistory::ASSIGNED,
                    ]
                );
            });
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function remove(Request $request, Punishment $punishment) {
        try {
            $timeZone = $punishment->dynamic()->first()->time_zone;

            if ($punishment->value > 0) {
                DB::transaction(function() use ($request, $punishment, $timeZone) {
                    $punishment->update(['value' => $punishment->value - 1]);
                    PunishmentHistory::Create(
                        [
                            'punishment_id' => $punishment->id,
                            'date' => Carbon::now($timeZone)->format('Y-m-d'),
                            'action' => $request->user()->isSub() ? PunishmentHistory::COMPLETE : PunishmentHistory::FORGIVEN,
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

    function totalAssignedCount(Request $request) {
        try {
            $punishents = $request
                ->user()
                ->dynamics()
                ->first()
                ->punishments()
                ->sum("value");

            return json_encode([
                'count' => strval($punishents),
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }

    function assign(Request $request, Punishment $punishment, Task $task) {
        try {
            $task->punishments()->attach($punishment->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function unassign(Request $request, Punishment $punishment, Task $task) {
        try {
            $task->punishments()->detach($punishment->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
