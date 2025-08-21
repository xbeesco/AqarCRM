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
        'value_type'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_value' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en ?: $model->name_ar);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en ?: $model->name_ar);
            }
        });
    }

    /**
     * The valid categories for features.
     *
     * @var array<string>
     */
    public static array $validCategories = [
        'basics', 'amenities', 'security', 'extras'
    ];



    /**
     * Get the localized name attribute.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
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
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order features by category.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category')->orderBy('name_en');
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
     * Get features grouped by category.
     */
    public static function getGroupedByCategory(): array
    {
        return self::ordered()
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