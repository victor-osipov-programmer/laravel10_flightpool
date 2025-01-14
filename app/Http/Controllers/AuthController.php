<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    function login(Request $request) {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $data['phone'])->where('password', $data['password'])->first();

        if (!isset($user)) {
            return response([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'errors' => ['phone or password incorrect']
                ]
            ], 401);
        }

        $new_token = Str::random(15);
        $user->update([
            'api_token' => $new_token
        ]);

        return [
            'data' => [
                'token' => $new_token
            ]
        ];
    }   
}
