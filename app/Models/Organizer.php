<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'telephone',
        'instagram',
        'full_name',
        'status',
        'signature_path',
        'status_Wa',
        'status'
    ];
}
