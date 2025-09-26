<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Message;
use App\Models\Task;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    function all(Request $request) {
        $messages = $request
            ->user()
            ->dynamics()
            ->first()
            ->messages()
            ->with(['tasks' => fn($q) => $q->select('tasks.id')])
            ->get();

        return json_encode([
            'messages' => $messages,
        ]);
    }

    function create(MessageRequest $request) {
        try {
            Message::create([
                'dynamic_id' => $request->user()->dynamics()->first()->id,
                'name' => $request->name,
                'description' => $request->description ?? ""
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function update(MessageRequest $request, Message $message) {
        try {
            $message->update([
                'name' => $request->name,
                'description' => $request->description ?? ""
            ]);}
         catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function delete(Request $request, Message $message) {
        try {
            DB::transaction(function() use ($request, $message) {
                $message->tasks()->detach();
                $message->delete();
            });
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function assign(Request $request, Message $message, Task $task) {
        try {
            $task->messages()->attach($message->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }

    function unassign(Request $request, Message $message, Task $task) {
        try {
            $task->messages()->detach($message->id);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }

        return json_encode(['message' => 'success']);
    }
}
