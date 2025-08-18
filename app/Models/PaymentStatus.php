<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentStatus extends Model
{
    // Status constants
    public const WORTH_COLLECTING = 1;
    public const COLLECTED = 2;
    public const DELAYED = 3;
    public const OVERDUE = 4;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'color',
        'icon',
        'description',
        'is_active',
        'is_paid_status',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paid_status' => 'boolean',
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

    public function scopePaidStatuses($query)
    {
        return $query->where('is_paid_status', true);
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

    public function getBadgeColorAttribute(): string
    {
        return $this->color ?? 'gray';
    }

    // Methods
    public function canTransitionTo(PaymentStatus $newStatus): bool
    {
        $validTransitions = [
            'worth_collecting' => ['collected', 'delayed', 'overdue'],
            'delayed' => ['collected', 'overdue'],
            'overdue' => ['collected'],
            'collected' => [], // Final state
        ];

        return in_array($newStatus->slug, $validTransitions[$this->slug] ?? []);
    }
}
