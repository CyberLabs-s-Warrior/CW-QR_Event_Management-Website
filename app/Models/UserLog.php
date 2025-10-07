<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLog extends Model
{
    /** @use HasFactory<\Database\Factories\UserLogFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'device_info',
        'status',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
