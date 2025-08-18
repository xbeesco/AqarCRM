<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PropertyFeature extends Model
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
        'category',
        'icon',
        'requires_value',
        'value_type',
        'value_options',
        'description_ar',
        'description_en',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_value' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'properties_count' => 'integer',
        'value_options' => 'array',
    ];

    /**
     * The valid categories for features.
     *
     * @var array<string>
     */
    public static array $validCategories = [
        'basics', 'amenities', 'security', 'extras'
    ];

    /**
     * The valid value types for features.
     *
     * @var array<string>
     */
    public static array $validValueTypes = [
        'boolean', 'number', 'text', 'select'
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
     * Get the localized category name.
     */
    public function getCategoryNameAttribute(): string
    {
        $categories = [
            'basics' => app()->getLocale() === 'ar' ? 'أساسيات' : 'Basics',
            'amenities' => app()->getLocale() === 'ar' ? 'مرافق' : 'Amenities',
            'security' => app()->getLocale() === 'ar' ? 'أمان' : 'Security',
            'extras' => app()->getLocale() === 'ar' ? 'إضافات' : 'Extras',
        ];

        return $categories[$this->category] ?? $this->category;
    }

    /**
     * Get the properties that have this feature.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_feature_property')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active features.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order features by category and sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category')->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Scope a query to filter by value type.
     */
    public function scopeByValueType(Builder $query, string $valueType): Builder
    {
        return $query->where('value_type', $valueType);
    }

    /**
     * Get formatted value based on the value type.
     */
    public function getFormattedValue(mixed $value): mixed
    {
        return match ($this->value_type) {
            'boolean' => (bool) $value,
            'number' => is_numeric($value) ? (float) $value : 0,
            'text' => (string) $value,
            'select' => $this->validateSelectValue($value) ? $value : null,
            default => $value,
        };
    }

    /**
     * Validate if a value is valid for this feature.
     */
    public function isValidValue(mixed $value): bool
    {
        return match ($this->value_type) {
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']),
            'number' => is_numeric($value),
            'text' => is_string($value),
            'select' => $this->validateSelectValue($value),
            default => true,
        };
    }

    /**
     * Validate select value against available options.
     */
    private function validateSelectValue(mixed $value): bool
    {
        if ($this->value_type !== 'select' || !$this->value_options) {
            return false;
        }

        return in_array($value, array_keys($this->value_options));
    }

    /**
     * Get the display value for a given value.
     */
    public function getDisplayValue(mixed $value): string
    {
        return match ($this->value_type) {
            'boolean' => $value ? (app()->getLocale() === 'ar' ? 'نعم' : 'Yes') 
                              : (app()->getLocale() === 'ar' ? 'لا' : 'No'),
            'select' => $this->value_options[$value] ?? (string) $value,
            default => (string) $value,
        };
    }

    /**
     * Update the properties count for this feature.
     */
    public function updatePropertiesCount(): void
    {
        $this->update([
            'properties_count' => $this->properties()->count()
        ]);
    }

    /**
     * Get all valid category options.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'basics' => app()->getLocale() === 'ar' ? 'أساسيات' : 'Basics',
            'amenities' => app()->getLocale() === 'ar' ? 'مرافق' : 'Amenities',
            'security' => app()->getLocale() === 'ar' ? 'أمان' : 'Security',
            'extras' => app()->getLocale() === 'ar' ? 'إضافات' : 'Extras',
        ];
    }

    /**
     * Get all valid value type options.
     */
    public static function getValueTypeOptions(): array
    {
        return [
            'boolean' => app()->getLocale() === 'ar' ? 'نعم/لا' : 'Yes/No',
            'number' => app()->getLocale() === 'ar' ? 'رقم' : 'Number',
            'text' => app()->getLocale() === 'ar' ? 'نص' : 'Text',
            'select' => app()->getLocale() === 'ar' ? 'اختيار من قائمة' : 'Select from List',
        ];
    }

    /**
     * Get features grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return self::active()
                  ->ordered()
                  ->get()
                  ->groupBy('category')
                  ->map(function ($features, $category) {
                      return [
                          'category' => $category,
                          'category_name' => self::getCategoryOptions()[$category] ?? $category,
                          'features' => $features
                      ];
                  })
                  ->values()
                  ->toArray();
    }
}