<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'cost_type',
        'amount',
        'currency',
        'billing_cycle',
        'next_renewal_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_renewal_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }
}
