<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request){
        $user = User::where('email',  $request->email)->first();
          if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password))
      {
            return response()->json([
                'message' => ['Username or password incorrect'],
            ],300);
        }

        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User logged in successfully',
            'name' => $user->name,
            'id' => $user->id,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }
        // public function logout(Request $request)
        // {
        //     $request->user()->tokens()->delete();

        //     return response()->json(['message' => 'Logged out successfully'], 200);
        // }
}
