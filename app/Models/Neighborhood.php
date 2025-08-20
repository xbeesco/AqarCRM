<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Neighborhood extends Model
{
    protected $table = 'locations';
    
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'parent_id',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('neighborhoods', function ($builder) {
            $builder->where('level', 4);
        });
        
        static::creating(function ($neighborhood) {
            $neighborhood->level = 4;
        });
    }
    
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'parent_id');
    }
    
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'location_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}