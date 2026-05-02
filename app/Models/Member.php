<?php
// app/Models/Member.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'hostel_id',
        'room_id',
        'bed_id',
        'with_food',
        'rent_amount',
        'addmissionform',
        'image',
        'aadharimage',
        'join_date',
        'exit_date',
        'status'
    ];

    protected $casts = [
        'with_food' => 'boolean',
        'join_date' => 'date',
        'exit_date' => 'date',
        'rent_amount' => 'decimal:2'
    ];

    // Relationships
    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors for image URLs
    public function getImageUrlAttribute()
    {
        if ($this->image && file_exists(public_path($this->image))) {
            return asset($this->image);
        }
        return asset('assets/images/default-avatar.png');
    }

    public function getAddmissionformUrlAttribute()
    {
        if ($this->addmissionform && file_exists(public_path($this->addmissionform))) {
            return asset($this->addmissionform);
        }
        return null;
    }

    public function getAadharimageUrlAttribute()
    {
        if ($this->aadharimage && file_exists(public_path($this->aadharimage))) {
            return asset($this->aadharimage);
        }
        return null;
    }

    // Get monthly rent based on food preference
    public function getCalculatedRentAttribute()
    {
        if ($this->room && $this->room->roomType) {
            if ($this->with_food) {
                return $this->room->roomType->rent_with_food;
            } else {
                return $this->room->roomType->rent_without_food;
            }
        }
        return $this->rent_amount;
    }
}
