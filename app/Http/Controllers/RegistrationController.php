<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Dynamic;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function registerNewUser(RegisterRequest $request) {
        try {
            DB::transaction(function() use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'user_name' => $request->user_name,
                    'role' => $request->role,
                    'password' => Hash::make($request->password),
                    'points' => 0,
                ]);

                if ($request->create_dynamic) {
                    $dynamic = Dynamic::create([
                        'name' => $request->dynamic_name,
                        'time_zone' => $request->dynamic_time_zone,
                        'default_reward_emojis' => '🎉🥳🎊',
                        'UUID' => Str::uuid(),
                    ]);
                    $dynamic->users()->attach($user->id);
                } else {
                    $dynamic = Dynamic::where('UUID', $request->dynamic_uuid)->first();
                    $dynamic->users()->attach($user->id);
                }
            });
            return json_encode(['message' => 'success']);
        } catch (Exception $e) {
            return json_encode(['message' => 'something went wrong']);
        }
    }
}
