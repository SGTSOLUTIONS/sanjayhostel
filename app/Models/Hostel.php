<?php
// app/Models/Hostel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $fillable = [
        'name', 'type', 'address', 'city', 'created_by'
    ];

    /**
     * Get the user who created this hostel.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alternative: Get the admin who created this hostel
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the rooms for this hostel.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Scopes for filtering
     */
    public function scopeMens($query)
    {
        return $query->where('type', 'mens');
    }

    public function scopeWomens($query)
    {
        return $query->where('type', 'womens');
    }
}
