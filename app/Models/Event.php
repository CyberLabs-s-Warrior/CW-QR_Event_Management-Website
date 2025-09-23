<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, HasTimestamps, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location',
        'event_category_id',
        'created_by',
        'status',
        'start_date',
        'end_date',
        'banner',
        'qr_logo'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];


    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function countAbsentAttendees()
    {
        return $this->attendance()
            ->where('status', 'absent')
            ->count();
    }

    public function countPresentOrLateAttendees()
    {
        return $this->attendance()
            ->whereIn('status', ['present', 'late'])
            ->count();
    }

    public function countAttendees()
    {
        return $this->attendance()
            ->count();
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'event_admins', 'event_id', 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function eventDetail()
    {
        return $this->hasOne(EventDetail::class);
    }

    public function eventPhotos()
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function registrationLink()
    {
        return $this->hasOne(EventRegistrationLink::class);
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function customInputs()
    {
        return $this->hasMany(CustomInputRegistration::class);
    }
}
