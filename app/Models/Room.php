<?php
// app/Models/Room.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'hostel_id',
        'room_type_id',
        'room_number'
    ];

    // Relationships
    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    // Accessors
    public function getFullDetailsAttribute()
    {
        return $this->room_number . ' - ' . ($this->hostel->name ?? 'N/A');
    }

    public function getAvailableBedsCountAttribute()
    {
        return $this->beds()->where('is_occupied', false)->count();
    }

    public function getOccupiedBedsCountAttribute()
    {
        return $this->beds()->where('is_occupied', true)->count();
    }

    public function getTotalBedsCountAttribute()
    {
        return $this->beds()->count();
    }
}
