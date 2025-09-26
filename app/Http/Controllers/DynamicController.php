<?php

namespace App\Http\Controllers;

use App\Http\Requests\DynamicRequest;
use App\Models\Dynamic;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DynamicController extends Controller
{
    function info(Request $request) {
        $dynamic = $request
            ->user()
            ->dynamics()
            ->first();

        $sub = $request->user()->dynamics()->first()->submissives()->first();
        $dom = $request->user()->dynamics()->first()->dominants()->first();

        return json_encode([
            'dynamic' => [
                'id' => $dynamic->id,
                'name' => $dynamic->name,
                'UUID' => $dynamic->UUID,
                'sub' => $sub->name ?? "",
                'dom' => $dom->name ?? "",
                'time_zone' => $dynamic->time_zone,
                'default_reward_emojis' => $dynamic->default_reward_emojis,
                'created_at' => Carbon::parse($dynamic->created_at)->format('F j, Y'),
                'created_at_humans' => Carbon::parse($dynamic->created_at)->diffForHumans(),
            ]
        ]);
    }

    // function create(Request $request) {
    //     try {
    //         Dynamic::create([
    //             'name' => $request->name,
    //             'time_zone' => $request->time_zone
    //         ]);
    //     } catch (Exception $e) {
    //         return json_encode(['message' => 'something went wrong']);
    //     }

    //     return json_encode(['message' => 'success']);
    // }

    function update(DynamicRequest $request, Dynamic $dynamic) {
        try {
            DB::transaction(function() use ($request, $dynamic) {
                $sub = $request->user()->dynamics()->first()->submissives()->first();
                $dom = $request->user()->dynamics()->first()->dominants()->first();

                if ($sub) {
                    $sub->update(['name' => $request->sub]);
                }

                if ($request->user()->isDom()) {
                    $dom->update(['name' => $request->dom]);

                    $dynamic->update([
                        'name' => $request->name,
                        'time_zone' => $request->time_zone,
                        'default_reward_emojis' => $request->default_reward_emojis
                    ]);
                }
            });
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
