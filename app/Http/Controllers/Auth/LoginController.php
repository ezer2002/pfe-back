<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LoginController extends Controller
{
    public function login (LoginRequest $request){
        $credentials = [
        'email' => $request->email,
        'password' => $request->password,
        ];
        if(auth()->attempt ($credentials )){
                /** @var \App\Models\User $user **/

        $user =Auth::user();
        $user->tokens()->delete();
        $success['token'] = $user->createToken(request()->userAgent())->plainTextToken;
        $success['name'] = $user->first_name;
        $success['success'] =true;
        return response()->json($success,200);}
        else{
            return response()->json(['error'=>"Unauthorised"]);
        }

        }
        // public function logout(Request $request)
        // {
        //     $request->user()->tokens()->delete();

        //     return response()->json(['message' => 'Logged out successfully'], 200);
        // }
    }