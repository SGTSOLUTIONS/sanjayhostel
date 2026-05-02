<?php
// app/Models/StaffAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendances';

    protected $fillable = [
        'staff_id',
        'attendance_date',
        'status',
        'leave_reason',
        'proof_image',
        'work_details',
        'notes',
        'marked_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the user who marked attendance
     */
    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Get proof image URL
     */
    public function getProofImageUrlAttribute()
    {
        if ($this->proof_image && file_exists(public_path($this->proof_image))) {
            return asset($this->proof_image);
        }
        return null;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'present' => 'bg-success',
            'leave' => 'bg-danger',
            'half_day' => 'bg-warning',
            'holiday' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'present' => 'Present',
            'leave' => 'On Leave',
            'half_day' => 'Half Day',
            'holiday' => 'Holiday',
            default => ucfirst($this->status)
        };
    }
}
