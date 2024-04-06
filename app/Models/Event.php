<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'banner_path',
        'thumbnail_path',
        'name',
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
}
