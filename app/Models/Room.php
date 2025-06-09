<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'room_type_id',
        'price',
        'description',
        'status_room_id',
    ];
    public function convenients()
    {
        return $this->belongsToMany(Convenient::class, 'room_convenient',);
    }
    public function roomType()
    {
        return $this->belongsTo(Room_types::class, 'room_type_id');
    }
    public function images()
    {
        return $this->hasMany(RoomImage::class, 'room_id');
    }
    public function status()
    {
        return $this->belongsTo(StatusRoom::class, 'status_room_id');
    }
    public function booking()
    {
        return $this->hasMany(Booking::class, 'room_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'room_id');
    }
}
