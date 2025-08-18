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
        'properties_count' => 'integer',
    ];

    /**
     * The valid color options for status badges.
     *
     * @var array<string>
     */
    public static array $validColors = [
        'gray', 'red', 'yellow', 'green', 'blue', 'indigo', 'purple', 'pink'
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name_en') && empty($model->slug)) {
                $model->slug = Str::slug($model->name_en);
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
     * Get the localized description attribute.
     */
    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * Get the properties with this status.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
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
     * Get the badge HTML for this status.
     */
    public function getBadgeAttribute(): string
    {
        $colorClasses = [
            'gray' => 'bg-gray-100 text-gray-800',
            'red' => 'bg-red-100 text-red-800',
            'yellow' => 'bg-yellow-100 text-yellow-800',
            'green' => 'bg-green-100 text-green-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'pink' => 'bg-pink-100 text-pink-800',
        ];

        $classes = $colorClasses[$this->color] ?? $colorClasses['gray'];
        
        return sprintf(
            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
            $classes,
            htmlspecialchars($this->name)
        );
    }

    /**
     * Get the badge color classes for Filament components.
     */
    public function getBadgeColorAttribute(): string
    {
        return $this->color;
    }

    /**
     * Check if properties with this status are available for rent.
     */
    public function isAvailableForRent(): bool
    {
        return $this->is_available && $this->is_active;
    }

    /**
     * Check if this status can transition to another status.
     */
    public function canTransitionTo(string $toStatus): bool
    {
        // Define valid status transitions
        $transitions = [
            'available' => ['rented', 'under-maintenance', 'reserved', 'unavailable'],
            'rented' => ['available', 'under-maintenance', 'unavailable'],
            'under-maintenance' => ['available', 'unavailable'],
            'reserved' => ['available', 'rented', 'unavailable'],
            'unavailable' => ['available', 'under-maintenance'],
        ];

        return in_array($toStatus, $transitions[$this->slug] ?? []);
    }

    /**
     * Update the properties count for this status.
     */
    public function updatePropertiesCount(): void
    {
        $this->update([
            'properties_count' => $this->properties()->count()
        ]);
    }

    /**
     * Get all valid color options.
     */
    public static function getColorOptions(): array
    {
        return array_combine(self::$validColors, array_map('ucfirst', self::$validColors));
    }
}