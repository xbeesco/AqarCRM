<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'sort_order',
    ];

    protected $casts = [
        'requires_value' => 'boolean',
        'value_options' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

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
     * Scope for active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
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
     * Get formatted value attribute based on value type
     */
    public function getFormattedValueAttribute($value): string
    {
        if (!$this->requires_value) {
            return $this->name;
        }

        switch ($this->value_type) {
            case 'boolean':
                return $value ? 'نعم / Yes' : 'لا / No';
            case 'select':
                $options = $this->value_options ?? [];
                return $options[$value] ?? $value;
            case 'number':
                return (string) $value;
            case 'text':
            default:
                return (string) $value;
        }
    }

    /**
     * Validate value based on feature type
     */
    public function validateValue($value): bool
    {
        if (!$this->requires_value) {
            return true;
        }

        switch ($this->value_type) {
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']);
            case 'number':
                return is_numeric($value);
            case 'select':
                $options = $this->value_options ?? [];
                return array_key_exists($value, $options);
            case 'text':
                return is_string($value) && strlen($value) <= 500;
            default:
                return true;
        }
    }

    /**
     * Get category options
     */
    public static function getCategoryOptions(): array
    {
        return [
            'basic' => 'أساسي / Basic',
            'amenities' => 'مرافق / Amenities',
            'safety' => 'أمان / Safety',
            'luxury' => 'رفاهية / Luxury',
            'services' => 'خدمات / Services',
        ];
    }

    /**
     * Get value type options
     */
    public static function getValueTypeOptions(): array
    {
        return [
            'boolean' => 'نعم/لا / Yes/No',
            'number' => 'رقم / Number',
            'text' => 'نص / Text',
            'select' => 'قائمة خيارات / Select List',
        ];
    }
}
