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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'properties_count' => 'integer',
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
        return $this->hasMany(PropertyType::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the properties of this type.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
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

    /**
     * Scope a query to only include root property types (no parent).
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full hierarchical path of the property type.
     */
    public function getFullPathAttribute(): string
    {
        $path = collect();
        $current = $this;

        while ($current) {
            $path->prepend($current->name);
            $current = $current->parent;
        }

        return $path->implode(' > ');
    }

    /**
     * Check if this property type has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Update the properties count for this property type.
     */
    public function updatePropertiesCount(): void
    {
        $this->update([
            'properties_count' => $this->properties()->count()
        ]);
    }
}