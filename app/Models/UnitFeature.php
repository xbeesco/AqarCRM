<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UnitFeature extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'category',
    ];

    protected $casts = [];

    /**
     * Get the units that have this feature
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_feature_unit')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

}
