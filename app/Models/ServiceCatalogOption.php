<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalogOption extends Model
{
    public const TYPE_SERVICE = 'service_type';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_COST_CATEGORY = 'cost_category';

    protected $fillable = [
        'catalog_type',
        'name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOfType(Builder $query, string $catalogType): Builder
    {
        return $query->where('catalog_type', $catalogType);
    }
}
