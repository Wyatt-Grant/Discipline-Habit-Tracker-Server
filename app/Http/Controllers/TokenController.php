<?php

namespace App\Http\Controllers;

use App\Http\Requests\APNRequest;
use App\Http\Requests\TokenRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    function auth(TokenRequest $request) {
        try {
            $user = User::where('user_name', $request->user_name)->first();

            return json_encode([
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'user' => $user->toArray(),
                'APN' => $request->APN,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => $e->getMessage()]);
        }
    }

    function setAPN(APNRequest $request) {
        try {
            $request->user()->update([
                'APN' => $request->APN,
                'device' => $request->device_name
            ]);

            return json_encode([
                'APN' => $request->APN,
            ]);
        } catch (Exception $e) {
            return json_encode(['message' => $e->getMessage()]);
        }
    }
}
