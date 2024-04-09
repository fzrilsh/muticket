<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $appends = ['author'];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'organizer_id',
        'banner_path',
        'thumbnail_path',
        'name',
        'slug',
        'category',
        'capacity',
        'start_at',
        'closed_at',
        'address',
        'region',
        'province',
        'city',
        'zip',
        'coordinates',
        'description',
        'refund_rules',
        'keywords'
    ];

    public function author(){
        return $this->hasOne(Organizer::class, 'id', 'organizer_id');
    }

    public function getAuthorAttribute(){
        return $this->author()->first()->pluck('name')[0];
    }

    public function getBannerPathAttribute($val){
        return Storage::url($val);
    }

    public function getThumbnailPathAttribute($val){
        return Storage::url($val);
    }

    public function tickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Price::class, 'event_id', 'id');
    }

    public function getCustomersAttribute(){
        $tickets = Ticket::where(['event_id' => $this->id, 'status' => 'booked'])->get()->toArray();
        foreach ($tickets as $key => $value){
            $tickets[$key] = [];
            $tickets[$key]['ticket_type'] = Price::find($value['price_id'])->name;
            $tickets[$key]['booked_at'] = $value['booked_at'];
            $tickets[$key]['email'] = User::find($value['user_id'])->email;
        }

        return $tickets;
    }
}
