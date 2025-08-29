<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasHierarchicalPath;

class Location extends Model
{
    use HasHierarchicalPath;
    protected $fillable = [
        'name',
        'code',
        'parent_id', 
        'level',
        'path',
        'coordinates',
        'postal_code',
        'is_active'
    ];
    
    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($location) {
            // منع تغيير المستوى بعد الإنشاء
            if ($location->exists && $location->isDirty('level')) {
                $location->level = $location->getOriginal('level');
            }
            
            // تحديد المستوى بناءً على الموقع الأب فقط عند الإنشاء
            if (!$location->exists) {
                if ($location->parent_id) {
                    $parent = self::find($location->parent_id);
                    if ($parent) {
                        $location->level = $parent->level + 1;
                    }
                } else {
                    $location->level = 1;
                }
            }
            
            // التحقق من صحة الموقع الأب عند التعديل
            if ($location->exists && $location->isDirty('parent_id') && $location->parent_id) {
                $parent = self::find($location->parent_id);
                if (!$parent || $parent->level != ($location->level - 1)) {
                    throw new \Exception('الموقع الأب المحدد غير صالح لهذا المستوى');
                }
            }
        });
        
        static::saved(function ($location) {
            // تحديث الـ path مرة واحدة فقط بدون حفظ إضافي
            $location->updatePathWithoutSaving();
        });
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }
    
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
    
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
    
    
    // Scopes for each level
    public function scopeCountries($query)
    {
        return $query->where('level', 1);
    }
    
    public function scopeCities($query)
    {
        return $query->where('level', 2);
    }
    
    public function scopeDistricts($query)
    {
        return $query->where('level', 3);
    }
    
    public function scopeNeighborhoods($query)
    {
        return $query->where('level', 4);
    }
    
    
    /**
     * Update the path field for hierarchical ordering without saving
     */
    public function updatePathWithoutSaving(): void
    {
        if (!$this->id) {
            return;
        }
        
        $newPath = $this->calculatePath();
        
        if ($this->path !== $newPath) {
            // تحديث الـ path مباشرة في قاعدة البيانات بدون trigger events
            self::where('id', $this->id)->update(['path' => $newPath]);
            $this->path = $newPath;
            
            // تحديث paths الأطفال
            $this->updateChildrenPaths();
        }
    }
    
    /**
     * Calculate the path for this location
     */
    protected function calculatePath(): string
    {
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);
            
            if ($parent) {
                // التأكد من أن الأب لديه path
                if (!$parent->path) {
                    $parent->updatePathWithoutSaving();
                }
                
                // الحصول على ترتيب هذا العنصر بين الأشقاء
                $siblings = self::where('parent_id', $this->parent_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->toArray();
                
                $siblingOrder = array_search($this->id, $siblings) + 1;
                
                return $parent->path . '/' . str_pad($siblingOrder, 4, '0', STR_PAD_LEFT);
            } else {
                return '/0001'; // fallback if parent not found
            }
        } else {
            // للعناصر الجذر
            $roots = self::whereNull('parent_id')
                ->orderBy('id')
                ->pluck('id')
                ->toArray();
            
            $rootOrder = array_search($this->id, $roots) + 1;
            return '/' . str_pad($rootOrder, 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Update the path field for hierarchical ordering (للاستخدام اليدوي فقط)
     */
    public function updatePath(): void
    {
        $this->updatePathWithoutSaving();
    }
    
    /**
     * Update all children paths when parent path changes
     */
    public function updateChildrenPaths(): void
    {
        $children = $this->children;
        foreach ($children as $child) {
            $child->updatePathWithoutSaving();
        }
    }
    
    /**
     * Get all descendants in hierarchical order
     */
    public static function getHierarchicalOrder()
    {
        return self::orderBy('path')->get();
    }
}
