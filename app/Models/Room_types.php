<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room_types extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'description',
    ];
    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }
}
