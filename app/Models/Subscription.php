<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'billing_cycle',
        'amount',
        'currency',
        'next_renewal_at',
        'notes',
        'has_trial',
        'trial_ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_renewal_at' => 'date',
            'has_trial' => 'boolean',
            'trial_ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function isInTrial(?CarbonInterface $onDate = null): bool
    {
        if (! $this->has_trial || ! $this->trial_ends_at) {
            return false;
        }

        $date = ($onDate ?? now())->copy()->startOfDay();

        return $this->trial_ends_at->copy()->startOfDay()->gte($date);
    }

    public function trialStatusLabel(?CarbonInterface $onDate = null): string
    {
        if (! $this->has_trial) {
            return 'Sin periodo de prueba';
        }

        $trialEnd = $this->trial_ends_at?->format('Y-m-d') ?? 'sin fecha';

        return $this->isInTrial($onDate)
            ? "En prueba hasta {$trialEnd}"
            : "Prueba finalizada ({$trialEnd})";
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
