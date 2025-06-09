<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'response',
        'responded_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
