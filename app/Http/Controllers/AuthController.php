<?php

namespace App\Http\Controllers;

use App\Mail\OrganizerValidation;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function Login(Request $request){
        if($this->validator([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);
        if(!Auth::attempt($request->only(['email', 'password']))) return response()->json(['message' => 'Email or Password incorrect']);

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
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

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

    public function RegisterOrganizer(Request $request): \Illuminate\Http\JsonResponse
    {
        if($this->validator([
            'name' => 'required',
            'full_name' => 'required',
            'telephone' => 'required|numeric|regex:/^628\d{9,12}$/',
            'instagram' => 'required|regex:/^[a-z0-9_]*$/',
            'photo_ktp' => 'required|image|mimes:jpg,png,jpeg',
            'nik_ktp' => 'required|numeric|regex:/^[0-9]{16}$/',
            'signed' => 'required|image|mimes:jpg,png,jpeg'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors]);
        if(Organizer::where('user_id', Auth::user()->id)->first()) return response()->json(['message' => 'You have submitted your registration, wait 1x24 during working hours for us to validate it'], 400);

        $signature = $request->file('signed');
        $signature_path = $signature->storeAs('/signature', name: Auth::user()->id.'.'.$signature->getClientOriginalExtension());

        $ktp = $request->file('photo_ktp');
        $ktp_path = $ktp->storeAs('/ktp', name: Auth::user()->id.'.'.$ktp->getClientOriginalExtension());

        Organizer::create([
            'user_id' => Auth::user()->id,
            'email' => Auth::user()->email,
            'name' => $request->name,
            'telephone' => $request->telephone,
            'instagram' => $request->instagram,
            'full_name' => $request->full_name,
            'signature_path' => $signature_path
        ]);

//        Mail::to('hai@fazrilsh.my.id')->send(new OrganizerValidation([
//            'ktp' => $ktp_path,
//            'signature' => $signature_path,
//            'nik' => $request->nik_ktp,
//            'full_name' => $request->full_name
//        ], ''));
//
//        $wa = new \Whatsapp('Click link below to authentication your telephone number\n\n');
//        $wa->send($request->telephone);

        return response()->json([
            'ktp' => $ktp_path,
            'signature' => $signature_path,
            'nik' => $request->nik_ktp,
            'full_name' => $request->full_name
        ]);
    }
}
