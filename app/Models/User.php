<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'phone_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function eventAdminAssigned()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')->withTimestamps();
    }

    public function activeEvents()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
            ->where('status', 'active')->orderBy('start_date', 'asc')
            ->withTimestamps();
    }

    public function getOngoingEventAttribute()
    {
        $ongoingEvents = $this->ongoingEvents;
        $count = $ongoingEvents->count();

        if ($count === 0) {
            return "No Ongoing Event";
        }

        $firstEvent = $ongoingEvents->first();
        return $count > 1
            ? $firstEvent->title . " + " . ($count - 1) . " more"
            : $firstEvent->title;
    }

    public function getOngoingCountAttribute()
    {
        return $this->ongoingEvents->count();
    }

    public function doneEvents()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
            ->with('eventCategory')
            ->where(function ($query) {
                $query->where('status', 'done')
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')
                            ->whereDate('end_date', '<', now());
                    });
            })
            ->withTimestamps();
    }

    public function ongoingEvents()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
            ->with('eventCategory')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())->orderBy('start_date', 'asc')
            ->withTimestamps();
    }

    public function upcomingEvents()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
            ->with('eventCategory')
            ->where('status', 'active')
            ->whereDate('start_date', '>', now()->format('Y-m-d'))->orderBy('start_date', 'asc')
            ->withTimestamps();
    }

    public function currentMonthEvents()
    {
        return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->where('status', 'active')
            ->orderBy('start_date', 'asc')
            ->withTimestamps();
    }

  public function eventHistory()
{
    return $this->belongsToMany(Event::class, 'event_admins', 'user_id', 'event_id')
        ->with('eventCategory')
        ->where(function ($query) {
            $query->where('status', 'done')
                  ->orWhere(function ($q) {
                      $q->where('status', 'active')
                        ->whereDate('end_date', '<', now());
                  });
        })
        ->orderBy('end_date', 'desc')
        ->withTimestamps();
}

    public function createdEvents()
    {
        return $this->hasMany(Event::class);
    }
}
