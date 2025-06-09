<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convenient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_convenient',
        'icon',
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_convenient');
    }
}
