<?php
// app/Models/RoomType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'is_ac',
        'sharing',
        'normal_cot_count',
        'bunker_cot_count',
        'rent_with_food',
        'rent_without_food',
        'description'
    ];

    protected $casts = [
        'is_ac' => 'boolean',
        'sharing' => 'integer',
        'normal_cot_count' => 'integer',
        'bunker_cot_count' => 'integer',
        'rent_with_food' => 'decimal:2',
        'rent_without_food' => 'decimal:2',
    ];

    // Accessor to get total cots
    public function getTotalCotsAttribute()
    {
        return $this->normal_cot_count + $this->bunker_cot_count;
    }

    // Accessor to get cot type description
    public function getCotTypeDescriptionAttribute()
    {
        $parts = [];
        if ($this->normal_cot_count > 0) {
            $parts[] = "{$this->normal_cot_count} Normal Cot(s)";
        }
        if ($this->bunker_cot_count > 0) {
            $parts[] = "{$this->bunker_cot_count} Bunker Cot(s)";
        }
        return implode(' + ', $parts);
    }

    // Accessor for AC status text
    public function getAcStatusAttribute()
    {
        return $this->is_ac ? 'AC Room' : 'Non-AC Room';
    }

    // Scope for AC rooms
    public function scopeAc($query)
    {
        return $query->where('is_ac', true);
    }

    // Scope for Non-AC rooms
    public function scopeNonAc($query)
    {
        return $query->where('is_ac', false);
    }
}
