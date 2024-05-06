<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Hash;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Hash as FacadesHash;

class RegisterController extends Controller
{
    public function register (RegisterRequest $request){
        $newuser= $request->validated();
        $newuser['password']=\Illuminate\Support\Facades\Hash::make($newuser['password']);


        $user =User::create($newuser);
        $success['token'] = $user->createToken('user', ['app:all'])->plainTextToken;
                $success['name'] = $user->first_name;
                $success['success'] = true;
                return response()->json($success, 200);
    }
}
