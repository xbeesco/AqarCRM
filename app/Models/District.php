<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
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
        static::addGlobalScope('districts', function ($builder) {
            $builder->where('level', 3);
        });
        
        static::creating(function ($district) {
            $district->level = 3;
        });
    }
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'parent_id');
    }
    
    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class, 'parent_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}