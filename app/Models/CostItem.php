<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostItem extends Model
{
    private const LEGACY_CATEGORY_LABELS = [
        'hosting' => 'Hosting',
        'license' => 'Licencia',
        'infra' => 'Infraestructura',
        'other' => 'Otro',
    ];

    protected $fillable = [
        'name',
        'category',
        'cost_type',
        'target_scope',
        'service_id',
        'subscription_id',
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
            'service_id' => 'integer',
            'subscription_id' => 'integer',
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
            return 'Cada '.$years.' año'.($years === 1 ? '' : 's');
        }

        return 'Cada '.$intervalMonths.' mes'.($intervalMonths === 1 ? '' : 'es');
    }

    public function categoryLabel(): string
    {
        return self::categoryLabelFromValue($this->category);
    }

    public static function categoryLabelFromValue(?string $category): string
    {
        $value = trim((string) $category);

        if ($value === '') {
            return '-';
        }

        return self::LEGACY_CATEGORY_LABELS[mb_strtolower($value)] ?? $value;
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
