<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/login/google', [\App\Http\Controllers\GoogleLoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/login/google/callback', [\App\Http\Controllers\GoogleLoginController::class, 'handleGoogleCallback']);

Route::get('/phone/verif/{id}', function ($request){
    $organizer = \App\Models\Organizer::where('user_id', $request[0])->first();
    if(!$organizer || $organizer->status_wa == 'clear') return redirect()->intended();

    $organizer->status_wa = 'clear';
    $organizer->save();
    return redirect('/user/profile');
});

Route::get('/organizer/verify/{id}', function ($request){
    $organizer = \App\Models\Organizer::where('user_id', $request[0])->first();
    if(!$organizer || $organizer->status == 'clear') return redirect()->intended();

    if($organizer->status_wa == 'clear'){
        $wa = new \App\Utils\Whatsapp(\App\Utils\Whatsapp::$HEAD.'Berkas kamu telah di verifikasi oleh administrator, selamat berinteraksi dengan layanan kami!');
        $wa->send($organizer->telephone);
    }

    $organizer->status = 'clear';
    $organizer->save();
    return back();
});

Route::get('/email/verify', function (){
    if(auth()->user()->email_verified_at) return redirect('/dashboard');
    return view('auth.verify-email');
})->middleware('auth:sanctum')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request){
    $request->fulfill();
    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request){
  \App\Jobs\sendMailJob::dispatch($request->user())->onQueue('default');

   return back()->with(['message' => 'Email verification successfully sent']);
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::resource('/organizer', \App\Http\Controllers\OrganizerController::class)
    ->name('index', 'organizer')
    ->name('store', 'organizer.register');
Route::resource('/events', \App\Http\Controllers\EventsController::class);
Route::resource('/tickets', \App\Http\Controllers\TicketController::class);
Route::post('/tickets/buy', [\App\Http\Controllers\TicketController::class, 'buyTicket'])->name('ticket.buy+');
