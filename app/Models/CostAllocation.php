<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostAllocation extends Model
{
    protected $fillable = [
        'cost_item_id',
        'service_id',
        'allocation_mode',
        'weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function costItem(): BelongsTo
    {
        return $this->belongsTo(CostItem::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
