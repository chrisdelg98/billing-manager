<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'service_id',
        'subscription_id',
        'status',
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
            'status' => 'string',
            'paid_at' => 'date',
            'covered_period_start' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function isPending(): bool
    {
        return (string) $this->status === self::STATUS_PENDING;
    }

    public function methodLabel(): string
    {
        return match ((string) $this->method) {
            self::METHOD_TRANSFER => 'Transferencia',
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_OTHER => 'Otro',
            default => ucfirst((string) $this->method),
        };
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
