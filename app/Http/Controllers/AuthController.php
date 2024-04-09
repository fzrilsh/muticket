<?php

namespace App\Http\Controllers;

use App\Jobs\sendWhatsappJob;
use App\Mail\OrganizerValidation;
use App\Models\Organizer;
use App\Models\User;
use App\Utils\Whatsapp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', only: ['Logout', 'RegisterOrganizer'])
        ];
    }

    public function Login(Request $request)
    {
        if ($this->validator([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);
        if (!Auth::attempt($request->only(['email', 'password']))) return response()->json(['message' => 'Email or Password incorrect']);

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('user_token')->plainTextToken;
        Auth::login($user, true);

        return response()->json([
            'message' => 'Login success',
            'access_token' => $token
        ]);
    }

    public function Register(Request $request)
    {
        if ($this->validator([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        Auth::login($user, true);

        $token = $user->createToken('user_token')->plainTextToken;
        return response()->json([
            'message' => 'Register success',
            'access_token' => $token
        ]);
    }

    public function RegisterOrganizer(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($this->validator([
            'name' => 'required',
            'full_name' => 'required',
            'telephone' => 'required|numeric|regex:/^628\d{9,12}$/',
            'instagram' => 'required|regex:/^[a-z0-9_]*$/',
            'photo_ktp' => 'required|image|mimes:jpg,png,jpeg|max:1024',
            'nik_ktp' => 'required|numeric|regex:/^[0-9]{16}$/',
            'signed' => 'required|image|mimes:jpg,png,jpeg'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors]);
        if (Organizer::where('user_id', Auth::user()->id)->first()) return response()->json(['message' => 'You have submitted your registration, wait 1x24 during working hours for us to validate it'], 400);
        if (!Auth::user()->email_verified_at && !Auth::user()->google_id) return response()->json(['message' => 'Please verify your email first'], 422);

        $signature = $request->file('signed');
        $signature_path = $signature->storeAs('/signature', name: Auth::user()->id . '.' . $signature->getClientOriginalExtension());

        $ktp = $request->file('photo_ktp');
        $ktp_path = $ktp->storeAs('/ktp', name: Auth::user()->id . '.' . $ktp->getClientOriginalExtension());

        $hash = Auth::user()->id . Str::random(4);
        Mail::to('fazriloke18@gmail.com')->send(new OrganizerValidation([
            'ktp' => $ktp_path,
            'signature' => $signature_path,
            'nik' => $request->nik_ktp,
            'full_name' => $request->full_name
        ], env('APP_URL') . "/organizer/verify/{$hash}"));

        sendWhatsappJob::dispatch($request->telephone, Whatsapp::$MESSAGE_VERIF . "*" . env('APP_URL') . "/phone/verif/{$hash}*")->onQueue('default');

        Organizer::create([
            'user_id' => Auth::user()->id,
            'email' => Auth::user()->email,
            'name' => $request->name,
            'telephone' => $request->telephone,
            'instagram' => $request->instagram,
            'full_name' => $request->full_name,
            'signature_path' => $signature_path
        ]);

        return response()->json([
            'message' => 'Registration success.'
        ]);
    }
}
