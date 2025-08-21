<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UnitStatus extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'color',
        'icon',
        'description_ar',
        'description_en',
        'is_available',
        'allows_tenant_assignment',
        'requires_maintenance',
        'is_active',
        'sort_order',
        'units_count'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'allows_tenant_assignment' => 'boolean',
        'requires_maintenance' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'units_count' => 'integer'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unitStatus) {
            if (empty($unitStatus->slug)) {
                $unitStatus->slug = Str::slug($unitStatus->name_en);
            }
        });
    }

    /**
     * Get the units with this status
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'status_id');
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
     * Scope a query to only include statuses that allow tenant assignment.
     */
    public function scopeAllowsTenantAssignment(Builder $query): Builder
    {
        return $query->where('allows_tenant_assignment', true);
    }

    /**
     * Scope a query to only include statuses that require maintenance.
     */
    public function scopeRequiresMaintenance(Builder $query): Builder
    {
        return $query->where('requires_maintenance', true);
    }

    /**
     * Scope a query to order statuses by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

}
