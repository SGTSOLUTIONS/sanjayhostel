<?php
// app/Models/Staff.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'position',
        'salary',
        'joining_date',
        'address',
        'aadhar_number',
        'profile_image',
        'hostel_id',
        'created_by',
        'status'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'joining_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the hostel this staff belongs to
     */
    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    /**
     * Get the user who created this staff
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get attendance records
     */
    public function attendances()
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /**
     * Get attendance for a specific date
     */
    public function getAttendanceForDate($date)
    {
        return $this->attendances()->where('attendance_date', $date)->first();
    }

    /**
     * Check if staff is present on a date
     */
    public function isPresentOnDate($date)
    {
        $attendance = $this->getAttendanceForDate($date);
        if (!$attendance) {
            return true; // Default present
        }
        return $attendance->status === 'present';
    }

    /**
     * Get present days count in month
     */
    public function getPresentDaysCount($month, $year)
    {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $leaves = $this->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('status', 'leave')
            ->count();

        $totalDays = date('t', strtotime($startDate));
        return $totalDays - $leaves;
    }

    /**
     * Get leave days count in month
     */
    public function getLeaveDaysCount($month, $year)
    {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('status', 'leave')
            ->count();
    }
}
