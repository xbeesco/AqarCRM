<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'description',
        'is_active',
        'requires_reference',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_reference' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function collectionPayments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
