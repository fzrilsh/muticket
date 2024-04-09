<?php

namespace App\Http\Controllers;

use App\Http\Middleware\OrganizerMiddleware;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventsController extends Controller implements HasMiddleware
{
    public $organizer;
    public function __construct()
    {
        if(Auth::check()) $this->organizer = Organizer::where('user_id', Auth::user()->id)->first();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', only: ['store', 'update', 'destroy']),
            new Middleware(OrganizerMiddleware::class, only: ['store', 'update', 'destroy'])
        ];
    }

    public function index()
    {
        $events = Event::orderBy('created_at', 'DESC')->get();
        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($this->validator([
            'banner' => 'required|image|mimes:png,jpeg,jpg|max:1024',
            'thumbnail' => 'required|image|mimes:png,jpeg,jpg|max:1024',
            'name' => 'required',
            'category' => 'required',
            'capacity' => 'required|integer',
            'start_at' => 'required|date',
            'closed_at' => 'required|date|after_or_equal:start_at',
            'address' => 'required',
            'region' => 'required',
            'province' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'coordinates' => 'required',
            'description' => 'required'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

        $slug = $this->createSlug($request->name);

        $banner = $request->file('banner');
        $banner_path = $banner->storeAs('/banner', name: $slug.'.'.$banner->getClientOriginalExtension());

        $thumbnail = $request->file('thumbnail');
        $thumbnail_path = $thumbnail->storeAs('/thumbnail', name: $slug.'.'.$thumbnail->getClientOriginalExtension());

        $data = $request->except(['banner', 'thumbnail']);
        $data['slug'] = $slug;
        $data['organizer_id'] = $this->organizer->id;
        $data['banner_path'] = $banner_path;
        $data['thumbnail_path'] = $thumbnail_path;

        $event = Event::create($data);
        foreach (range(1, $request->capacity) as $i){
            Ticket::create([
                'event_id' => $event->id
            ]);
        }

        return response()->json($event);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event = Event::with(['tickets'])->where('slug', $id)->first();
        return response()->json($event);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if($this->validator([
            'banner' => 'image|mimes:png,jpeg,jpg|max:1024',
            'thumbnail' => 'image|mimes:png,jpeg,jpg|max:1024',
            'name' => 'string',
            'category' => 'string',
            'capacity' => 'integer',
            'start_at' => 'date',
            'closed_at' => 'date|after_or_equal:start_at',
            'address' => 'string',
            'region' => 'string',
            'province' => 'string',
            'city' => 'string',
            'zip' => 'string',
            'coordinates' => 'string',
            'description' => 'string'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

        $event = Event::find($id);
        if(!$event) return response()->json(['message' => 'Event not found'], 404);
        if($this->organizer->id != $event->organizer_id) return response()->json(['message' => 'Forbidden access'], 403);

        $event->update($request->all());
        return response()->json(['message' => 'Updatesu succes', 'data' => $request->all()]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
