<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySnapshot extends Model
{
    protected $fillable = [
        'period',
        'service_id',
        'income_total',
        'direct_cost_total',
        'shared_cost_total',
        'net_margin',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'income_total' => 'decimal:2',
            'direct_cost_total' => 'decimal:2',
            'shared_cost_total' => 'decimal:2',
            'net_margin' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
