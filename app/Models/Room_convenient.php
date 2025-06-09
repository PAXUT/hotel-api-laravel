<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room_convenient extends Model
{
    use HasFactory;

    // protected $table = 'room_convenients';
    protected $fillable = ['room_id', 'convenient_id'];
}
