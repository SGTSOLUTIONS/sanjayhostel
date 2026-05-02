<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'profile', 'phone',
        'city', 'gender', 'date_of_birth', 'status', 'storage_path'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function hostels()
    {
        return $this->belongsToMany(Hostel::class, 'admin_hostel', 'admin_id', 'hostel_id');
    }

    /**
     * Check if user is superadmin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get hostels that this admin can manage
     */
    public function getManagedHostels()
    {
        if ($this->isSuperAdmin()) {
            return Hostel::all();
        }
        return $this->hostels;
    }
}
