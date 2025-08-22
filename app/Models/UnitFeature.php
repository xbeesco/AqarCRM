<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UnitFeature extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'category',
        'icon',
        'description_ar',
        'description_en',
        'requires_value',
        'value_type',
        'value_options',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'requires_value' => 'boolean',
        'value_options' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unitFeature) {
            if (empty($unitFeature->slug)) {
                $unitFeature->slug = Str::slug($unitFeature->name_en);
            }
        });
    }

    /**
     * Get the units that have this feature
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_unit_feature')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Scope by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include active features.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order features by sort order and category.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category')->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Get the localized name based on current locale
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
            'basic' => app()->getLocale() === 'ar' ? 'أساسيات' : 'Basic',
            'amenities' => app()->getLocale() === 'ar' ? 'مرافق' : 'Amenities',
            'safety' => app()->getLocale() === 'ar' ? 'أمان' : 'Safety',
            'luxury' => app()->getLocale() === 'ar' ? 'فاخر' : 'Luxury',
            'services' => app()->getLocale() === 'ar' ? 'خدمات' : 'Services',
        ];

        return $categories[$this->category] ?? $this->category;
    }

    /**
     * Get category options for forms.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'basic' => app()->getLocale() === 'ar' ? 'أساسيات' : 'Basic',
            'amenities' => app()->getLocale() === 'ar' ? 'مرافق' : 'Amenities',
            'safety' => app()->getLocale() === 'ar' ? 'أمان' : 'Safety',
            'luxury' => app()->getLocale() === 'ar' ? 'فاخر' : 'Luxury',
            'services' => app()->getLocale() === 'ar' ? 'خدمات' : 'Services',
        ];
    }

    /**
     * Get value type options for forms.
     */
    public static function getValueTypeOptions(): array
    {
        return [
            'boolean' => app()->getLocale() === 'ar' ? 'نعم/لا' : 'Yes/No',
            'number' => app()->getLocale() === 'ar' ? 'رقم' : 'Number',
            'text' => app()->getLocale() === 'ar' ? 'نص' : 'Text',
            'select' => app()->getLocale() === 'ar' ? 'قائمة اختيار' : 'Select',
        ];
    }

}
