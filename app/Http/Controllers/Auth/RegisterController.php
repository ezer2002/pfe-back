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

public function register(Request $request)
{
      $validatedData = $request->validate([
          'name' => 'required|string|max:255',
          'email' => 'required|string|email|max:255|unique:users',
          'socite' => 'required|string|max:255',
          'tel' => 'required|string|max:255',
          'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'socite' => $validatedData['socite'],
            'tel' => $validatedData['tel'],
            'password' => \Illuminate\Support\Facades\Hash::make($validatedData['password']),
        ]);
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }}
