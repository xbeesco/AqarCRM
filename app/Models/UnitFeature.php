<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UnitFeature extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        //
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unitFeature) {
            if (empty($unitFeature->slug)) {
                $unitFeature->slug = Str::slug($unitFeature->name);
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
     * Scope a query to order features by name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
