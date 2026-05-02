<?php
// app/Models/Bed.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $fillable = [
        'room_id',
        'bed_number',
        'bed_type',
        'is_occupied',
        'current_member_id'
    ];

    protected $casts = [
        'is_occupied' => 'boolean'
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function currentMember()
    {
        return $this->belongsTo(Member::class, 'current_member_id');
    }

    // Accessors
    public function getBedTypeIconAttribute()
    {
        return $this->bed_type === 'bunker' ? '🪜' : '🛏️';
    }

    public function getBedTypeBadgeAttribute()
    {
        return $this->bed_type === 'bunker'
            ? '<span class="badge bg-warning"><i class="bi bi-stairs"></i> Bunker</span>'
            : '<span class="badge bg-success"><i class="bi bi-bed"></i> Normal</span>';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_occupied
            ? '<span class="badge bg-danger"><i class="bi bi-person-fill"></i> Occupied</span>'
            : '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Vacant</span>';
    }

    // Scopes
    public function scopeVacant($query)
    {
        return $query->where('is_occupied', false);
    }

    public function scopeOccupied($query)
    {
        return $query->where('is_occupied', true);
    }

    public function scopeNormal($query)
    {
        return $query->where('bed_type', 'normal');
    }

    public function scopeBunker($query)
    {
        return $query->where('bed_type', 'bunker');
    }
}
