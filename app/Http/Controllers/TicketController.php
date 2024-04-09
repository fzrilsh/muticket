<?php

namespace App\Http\Controllers;

use AllowDynamicProperties;
use App\Http\Middleware\OrganizerMiddleware;
use App\Jobs\paymentUpdateJob;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Price;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller implements HasMiddleware
{
    public $organizer;
    public function __construct()
    {
        $this->organizer = Organizer::where('user_id', Auth::user()->id)->first();
    }

    public function checkCapacity(int $capacity, Event $event): bool
    {
        $tickets = Ticket::where('event_id', $event->id)->count();
        $prices = Price::where('event_id', $event->id)->get();
        $tempCapacity = $capacity;

        foreach ($prices as $price) {
            $capacity += (int)$price->capacity;
        }

        if($tickets < $tempCapacity || $tickets < $capacity) return false;
        else return true;
    }

    public function tryLockTicket(Event $event, Price $price, Ticket $ticket = null): bool{
        if($ticket){
            if(Ticket::find($ticket->id)->user_id == Auth::user()->id){
                paymentUpdateJob::dispatch($ticket)->delay(now()->addMinutes(5));
                return true;
            }
            else{
                $ticket->update([
                    'user_id' => null,
                    'price_id' => null,
                    'status' => 'nothing'
                ]);
                return false;
            }
        }

        $tickets = Ticket::where(['event_id' => $event->id, 'price_id' => $price->id]);
        if(!($price->capacity - $tickets->count())) return false;

        $ticket = Ticket::where(['event_id' => $event->id, 'status' => 'nothing'])->first();
        if(!$ticket) return false;

        $ticket->update([
            'user_id' => Auth::user()->id,
            'price_id' => $price->id,
            'status' => 'pending'
        ]);
        return $this->tryLockTicket($event, $price, $ticket);
    }

    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware(OrganizerMiddleware::class)
        ];
    }

    public function index(){
        $events = Event::with(['tickets'])->where('organizer_id', $this->organizer->id)->get()->append(['customers'])->toArray();
        foreach ($events as $key => $value){
            $events[$key] = [];
            $events[$key]['name'] = $value['name'];
            $events[$key]['tickets'] = $value['tickets'];
            $events[$key]['customers'] = $value['customers'];
        }
        return response()->json($events);
    }

    public function store(Request $request){
        if($this->validator([
            'event_id' => 'required|exists:events,id',
            'name' => 'required',
            'price' => 'required|integer',
            'capacity' => 'required|integer',
            'max' => 'integer'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors]);

        $event = Event::find($request->event_id);
        if($this->organizer->id != $event->organizer_id) return response()->json(['message' => 'Forbidden access'], 403);
        if(!$this->checkCapacity($request->capacity, $event)){
            $this->validation->errors()->add('capacity', 'The maximum number of '.$request->name.' ticket purchases exceeds the event capacity limit!');
            return response()->json(['message' => 'Invalid field', 'errors' => $this->validation->errors()], 422);
        }

        Price::create($request->all());
        return response()->json(['message' => 'Successfully added a new ticket type for the '.$event->name.' event']);
    }

    public function update(Request $request, string $id){
        $price = Price::find($id);
        if($this->validator([
            'name' => 'string',
            'price' => 'integer',
            'capacity' => 'integer',
            'max' => 'integer'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors]);
        if(!$price) return response()->json(['message' => 'The ticket not found.'], 404);

        $event = Event::find($price->event_id);
        $capacity = $request->capacity - $price->capacity;
        if($this->organizer->id != $event->organizer_id) return response()->json(['message' => 'Forbidden access'], 403);
        if($capacity and !$this->checkCapacity($capacity, $event)){
            $this->validation->errors()->add('capacity', 'The maximum number of '.$request->name.' ticket purchases exceeds the event capacity limit!');
            return response()->json(['message' => 'Invalid field', 'errors' => $this->validation->errors()], 422);
        }

        $price->update($request->all());
        return response()->json(['message' => 'Update success.', 'data' => $request->all()]);
    }

    public function destroy(Request $request, string $id){
        if($this->validator([
            'alternative_id' => 'exists:prices,id'
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors], 422);

        $price = Price::find($id);
        if($price and $this->organizer->id != Event::find($price->event_id)->organizer_id) return response()->json(['message' => 'Forbidden access'], 403);
        if($price and Ticket::where('price_id', $id)->count()){
            if(!$request->alternative_id or $request->alternative_id == $id) return response()->json(['message' => 'Please add alternative_id field, because the ticket is booked by customers'], 409);
            Ticket::where('price_id', $id)->each(function ($ticket) use($request){
                $ticket->update(['price_id' => $request->alternative_id]);
            });
        }

        return response()->json(['message' => 'Delete success.']);
    }

    public function buyTicket(Request $request){
        if($this->validator([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array|min:1',
            'tickets.*.id' => 'required|exists:prices,id',
            'tickets.*.count' => 'required|integer',
        ])->fails()) return response()->json(['message' => 'Invalid field', 'errors' => $this->validation_errors]);

        $fails = [];
        foreach ($request->tickets as $ticket){

            if(!(Price::find($ticket['id'])->available - $ticket['count'])) return array_push($fails, ['name' => $ticket->name, 'reason' => 'Out of stock']);
            if($ticket['count'] > Price::find($ticket['id'])->max) return response()->json(['message' => 'Maximal to order ticket are 2.'], 400);
            foreach (range(1, $ticket['count']) as $count){
                $ticket = Price::find($ticket['id']);
                if($ticket->event_id != $request->event_id) return array_push($fails, ['name' => $ticket->name, 'reason' => 'Ticket not found']);
                if(!$ticket->available) return array_push($fails, ['name' => $ticket->name, 'reason' => 'Out of stock']);

                $try = $this->tryLockTicket(Event::find($ticket->event_id), $ticket);
                if(!$try){
                    array_push($fails, ['name' => $ticket->name, 'reason' => 'Out of stock']);
                    break;
                }
            }
        }

        if(count($fails)) return response()->json(['message' => 'Failed to buy ticket', 'fails' => $fails], 404);
        else return response()->json(['message' => 'Success buy all ticket']);
    }
}
