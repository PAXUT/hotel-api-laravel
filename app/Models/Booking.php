<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'user_id',
        'check_in_date',
        'check_out_date',
        'guests',
        'total_price',
        'status_id',
        'refund',
        'payment_method',
        'payment_status	',
        'payment_countdown',
        'payment_reference',
        'review',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
