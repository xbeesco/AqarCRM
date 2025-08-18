<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'allows_tenant_assignment' => 'boolean',
        'requires_maintenance' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'units_count' => 'integer',
    ];

    /**
     * Get the units with this status
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'status_id');
    }

    /**
     * Scope for active statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available statuses
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get the localized description based on current locale
     */
    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * Get badge color for display
     */
    public function getBadgeColorAttribute(): string
    {
        return $this->color;
    }

    /**
     * Check if this status can transition to another status
     */
    public function canTransitionTo(UnitStatus $toStatus): bool
    {
        // Basic business rules for status transitions
        if (!$this->is_active || !$toStatus->is_active) {
            return false;
        }

        // Maintenance can transition to any available status
        if ($this->slug === 'maintenance') {
            return $toStatus->is_available;
        }

        // Available can transition to occupied or maintenance
        if ($this->slug === 'available') {
            return in_array($toStatus->slug, ['occupied', 'maintenance', 'reserved']);
        }

        // Occupied can transition to available or maintenance
        if ($this->slug === 'occupied') {
            return in_array($toStatus->slug, ['available', 'maintenance']);
        }

        // Reserved can transition to occupied or available
        if ($this->slug === 'reserved') {
            return in_array($toStatus->slug, ['occupied', 'available']);
        }

        return true;
    }
}
