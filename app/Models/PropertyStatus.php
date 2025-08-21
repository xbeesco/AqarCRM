<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PropertyStatus extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'color',
        'icon',
        'description_ar',
        'description_en',
        'is_available',
        'is_active',
        'sort_order',
        'properties_count'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'properties_count' => 'integer'
    ];


    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($propertyStatus) {
            if (empty($propertyStatus->slug)) {
                $propertyStatus->slug = Str::slug($propertyStatus->name_en);
            }
        });
    }

    /**
     * Get the localized name attribute.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Scope a query to only include active statuses.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include available statuses.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to order statuses by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Get the properties with this status.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

}