<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'locations';
    
    protected $fillable = [
        'name',
        'name_ar',
        'name_en', 
        'code',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    protected static function booted()
    {
        static::addGlobalScope('countries', function ($builder) {
            $builder->where('level', 1);
        });
        
        static::creating(function ($country) {
            $country->level = 1;
            $country->parent_id = null;
        });
    }
    
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'parent_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}