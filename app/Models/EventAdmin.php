<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAdmin extends Model
{
    /** @use HasFactory<\Database\Factories\EventAdminFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id'
    ];

    public function event() {
        return $this->belongsTo(Event::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
