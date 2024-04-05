<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function Login(Request $request){
        if($this->validator([
            'email' => 'required|email',
            'password' => 'required|password'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);
        if(!Auth::attempt($request->all())) return response()->json(['message' => 'Email or Password incorrect']);

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'access_token' => $token
        ]);
    }

    public function Register(Request $request){
        if($this->validator([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ])) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        Auth::login($user);

        $token = $user->createToken('user_token')->plainTextToken;
        return response()->json([
            'message' => 'Register success',
            'access_token' => $token
        ]);
    }
}
