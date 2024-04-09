<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $user = Socialite::driver('google')->user();
        $existingUser = User::where(['google_id' => $user->id])->orWhere('email', $user->email)->first();

        if ($existingUser) {
            Auth::login($existingUser, true);
        } else {
            $user = User::create([
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => Date::now(),
                'google_id' => $user->id,
                'password' => Hash::make(\request(Str::random()))
            ]);
            Auth::login($user, true);
        }

        return redirect()->intended('/');
    }
}
