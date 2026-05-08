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
        'billing_contact_name',
        'billing_contact_email',
        'billing_contact_whatsapp',
        'has_trial',
        'trial_ends_at',
        'is_active',
        'license_api_enabled',
        'license_code',
        'license_secret_hash',
        'license_secret_encrypted',
        'license_secret_hint',
        'license_key_rotated_at',
        'license_key_revoked_at',
        'license_last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_renewal_at' => 'date',
            'has_trial' => 'boolean',
            'trial_ends_at' => 'date',
            'is_active' => 'boolean',
            'license_api_enabled' => 'boolean',
            'license_key_rotated_at' => 'datetime',
            'license_key_revoked_at' => 'datetime',
            'license_last_used_at' => 'datetime',
        ];
    }

    public function hasLicenseCredentials(): bool
    {
        return ! empty($this->license_code) && ! empty($this->license_secret_hash);
    }

    public function daysUntilRenewal(?CarbonInterface $onDate = null): ?int
    {
        if (! $this->next_renewal_at) {
            return null;
        }

        $baseDate = ($onDate ?? now())->copy()->startOfDay();

        return $baseDate->diffInDays($this->next_renewal_at->copy()->startOfDay(), false);
    }

    public function renewalRiskLevel(?CarbonInterface $onDate = null): string
    {
        $days = $this->daysUntilRenewal($onDate);

        if ($days === null || ! $this->is_active) {
            return 'safe';
        }

        if ($days <= 2) {
            return 'danger';
        }

        if ($days <= 10) {
            return 'warning';
        }

        return 'safe';
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
