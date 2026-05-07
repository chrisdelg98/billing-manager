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
        'billing_interval_months',
        'next_renewal_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_interval_months' => 'integer',
            'next_renewal_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function monthlyAmount(): float
    {
        $intervalMonths = max((int) ($this->billing_interval_months ?: 1), 1);

        return (float) $this->amount / $intervalMonths;
    }

    public function billingFrequencyLabel(): string
    {
        if ($this->billing_cycle === 'monthly') {
            return 'Mensual';
        }

        if ($this->billing_cycle === 'yearly') {
            return 'Anual';
        }

        $intervalMonths = max((int) ($this->billing_interval_months ?: 1), 1);

        if ($intervalMonths % 12 === 0) {
            $years = (int) ($intervalMonths / 12);
            return 'Cada '.$years.' anio'.($years === 1 ? '' : 's');
        }

        return 'Cada '.$intervalMonths.' mes'.($intervalMonths === 1 ? '' : 'es');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }
}
