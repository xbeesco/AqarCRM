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
        'name',
        'slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        //
    ];


    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($propertyStatus) {
            if (empty($propertyStatus->slug)) {
                $propertyStatus->slug = Str::slug($propertyStatus->name);
            }
        });
    }


    /**
     * Scope a query to order statuses by name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Get the properties with this status.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'status_id');
    }
}
