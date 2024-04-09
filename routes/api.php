<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'Login']);
Route::post('/organizer', [\App\Http\Controllers\AuthController::class, 'RegisterOrganizer']);
