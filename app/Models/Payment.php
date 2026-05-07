<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'service_id',
        'subscription_id',
        'paid_at',
        'covered_period_start',
        'amount',
        'currency',
        'method',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'covered_period_start' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function coveredPeriodLabel(): string
    {
        return $this->covered_period_start?->format('Y-m') ?? '-';
    }

    public function coverageTimingLabel(): string
    {
        if (! $this->covered_period_start || ! $this->paid_at) {
            return '-';
        }

        $paidMonth = $this->paid_at->copy()->startOfMonth();
        $coveredMonth = $this->covered_period_start->copy()->startOfMonth();

        if ($coveredMonth->gt($paidMonth)) {
            return 'Anticipado';
        }

        if ($coveredMonth->lt($paidMonth)) {
            return 'Atrasado';
        }

        return 'Al dia';
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
