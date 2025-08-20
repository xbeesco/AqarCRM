<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
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
        static::addGlobalScope('cities', function ($builder) {
            $builder->where('level', 2);
        });
        
        static::creating(function ($city) {
            $city->level = 2;
        });
    }
    
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'parent_id');
    }
    
    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'parent_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}