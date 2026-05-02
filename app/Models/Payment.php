<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'month',
        'amount',
        'status',
        'paid_date',
        'payment_method',
        'transaction_id',
        'receipt_number',
        'notes',
        'late_fee',
        'discount',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'paid' => 'bg-success',
            'pending' => 'bg-warning',
            'overdue' => 'bg-danger',
            'partial' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'paid' => 'fa-check-circle',
            'pending' => 'fa-clock',
            'overdue' => 'fa-exclamation-triangle',
            'partial' => 'fa-adjust',
            default => 'fa-question-circle'
        };
    }

    public function getPaymentMethodBadgeAttribute()
    {
        return match($this->payment_method) {
            'cash' => 'bg-success',
            'card' => 'bg-primary',
            'bank_transfer' => 'bg-info',
            'upi' => 'bg-dark',
            default => 'bg-secondary'
        };
    }

    public function getTotalAmountAttribute()
    {
        return ($this->amount + $this->late_fee) - $this->discount;
    }

    public function getMonthNameAttribute()
    {
        return date('F Y', strtotime($this->month . '-01'));
    }

    // Scopes for advanced filtering
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopePaidBetween($query, $fromDate, $toDate)
    {
        return $query->whereBetween('paid_date', [$fromDate, $toDate]);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('transaction_id', 'like', "%{$searchTerm}%")
              ->orWhere('receipt_number', 'like', "%{$searchTerm}%")
              ->orWhereHas('member', function($m) use ($searchTerm) {
                  $m->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%");
              });
        });
    }
}
