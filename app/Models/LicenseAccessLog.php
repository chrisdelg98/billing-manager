<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'license_code',
        'ip_address',
        'user_agent',
        'result_status',
        'http_status',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
