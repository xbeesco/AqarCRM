<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitStatus extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'color',
    ];

    protected $casts = [];

    /**
     * Get the units with this status
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'status_id');
    }


    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

}
