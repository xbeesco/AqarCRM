<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PropertyType extends Model
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
        'icon',
        'description_ar',
        'description_en',
        'parent_id',
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
        'parent_id' => 'integer',
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

        static::creating(function ($propertyType) {
            if (empty($propertyType->slug)) {
                $propertyType->slug = Str::slug($propertyType->name_en);
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
     * Get the parent property type.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'parent_id');
    }

    /**
     * Get the child property types.
     */
    public function children(): HasMany
    {
        return $this->hasMany(PropertyType::class, 'parent_id');
    }



    /**
     * Get the properties of this type.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'type_id');
    }


    /**
     * Scope a query to only include active property types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order property types by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }




}