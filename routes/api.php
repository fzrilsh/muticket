<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (){
    Route::post('/organizer_register', [\App\Http\Controllers\AuthController::class, 'RegisterOrganizer']);
});
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'Login']);
Route::post('/register', [\App\Http\Controllers\AuthController::class, 'Register']);
