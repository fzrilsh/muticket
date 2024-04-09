<?php

namespace App\Http\Middleware;

use App\Models\Organizer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrganizerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if(!Organizer::where(['user_id' => Auth::user()->id, 'status' => 'clear', 'status_wa' => 'clear'])->first()) return redirect('organizer')->with(['register' => true]);
//            return \response()->json(['message' => 'Complete or please register your organization to register the event on our service'], 401);
        return $next($request);
    }

}
