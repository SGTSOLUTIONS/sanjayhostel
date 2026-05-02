<?php
// app/Models/Expense.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'expense_name',
        'amount',
        'month',
        'expense_date',
        'category',
        'note',
        'payment_method',
        'receipt',
        'created_by',
        'hostel_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who created this expense
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the hostel this expense belongs to
     */
    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    /**
     * Get month name attribute
     */
    public function getMonthNameAttribute()
    {
        return date('F Y', strtotime($this->month . '-01'));
    }

    /**
     * Get formatted amount attribute
     */
    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    /**
     * Scope for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month', date('Y-m'));
    }

    /**
     * Scope by month
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for accessible expenses (by hostel or global)
     * Fix: This is the method that was missing
     */
    public function scopeAccessible($query, $hostelIds)
    {
        return $query->where(function($q) use ($hostelIds) {
            $q->whereNull('hostel_id')
              ->orWhereIn('hostel_id', $hostelIds);
        });
    }
}
