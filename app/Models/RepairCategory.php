<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairCategory extends Model
{
    // Category constants
    public const GENERAL = 1;
    public const SPECIAL = 2;
    public const GOVERNMENT_UNIT = 3;
    public const GOVERNMENT_PROPERTY = 4;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'description',
        'affects_property',
        'affects_unit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'affects_property' => 'boolean',
        'affects_unit' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function repairs(): HasMany
    {
        return $this->hasMany(PropertyRepair::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProperty($query)
    {
        return $query->where('affects_property', true);
    }

    public function scopeForUnit($query)
    {
        return $query->where('affects_unit', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
