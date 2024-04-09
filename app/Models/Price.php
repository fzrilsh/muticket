<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $appends = [
        'available'
    ];

    protected $hidden = [
        'event_id',
        'created_at',
        'updated_at'
    ];


    protected $fillable = [
        'event_id',
        'name',
        'price',
        'capacity',
        'max',
        'status',
        'notes'
    ];

    public function event(){
        return $this->hasOne(Event::class, 'id', 'event_id');
    }

    public function tickets(){
        return $this->hasMany(Ticket::class, 'event_id', 'event_id');
    }

    public function getAvailableAttribute(){
        $tickets = Ticket::where(['event_id' => $this->event_id, 'price_id' => $this->id]);
        return $this->max - $tickets->count();
    }
}
