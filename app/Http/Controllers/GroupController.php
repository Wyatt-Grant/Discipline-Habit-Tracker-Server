<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use Exception;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    function all(Request $request) {
        $groups = $request
            ->user()
            ->dynamics()
            ->first()
            ->groups()
            ->orderBy('groups.sort_order')
            ->get();

        return json_encode([
            'groups' => $groups,
        ]);
    }

    function create(GroupRequest $request) {
        try {
            $dynamic = $request->user()->dynamics()->first();
            $maxSort = $dynamic->groups()->orderBy('groups.sort_order', 'DESC')->first()->sort_order ?? 0;

            Group::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'sort_order' => $maxSort + 1,
                'name' => $request->name,
                'color' => $request->color
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(GroupRequest $request, Group $group) {
        try {
            $group->update([
                'name' => $request->name,
                'color' => $request->color
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function updateSort(Request $request) {
        try {
            $groupIds = explode(',', $request->sorted_group_ids);
            for ($i = 0; $i < count($groupIds); $i++) {
                Group::where('id', $groupIds[$i])
                    ->update(['sort_order' => $i + 1]);
            }
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Group $group) {
        try {
            $group->delete();
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
