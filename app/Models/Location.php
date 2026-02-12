<?php

namespace App\Models;

use InvalidArgumentException;
use App\Models\Traits\HasHierarchicalPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasHierarchicalPath;

    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'path',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Location $location): void {
            // Prevent level change after creation
            if ($location->exists && $location->isDirty('level')) {
                $location->level = $location->getOriginal('level');
            }

            // Set level based on parent when creating
            if (! $location->exists) {
                if ($location->parent_id) {
                    $parent = self::find($location->parent_id);
                    if ($parent) {
                        $location->level = $parent->level + 1;
                    }
                } else {
                    $location->level = 1;
                }
            }

            // Validate parent when updating
            if ($location->exists && $location->isDirty('parent_id') && $location->parent_id) {
                $parent = self::find($location->parent_id);
                if (! $parent || $parent->level !== ($location->level - 1)) {
                    throw new InvalidArgumentException('Invalid parent location for this level');
                }
            }
        });

        static::saved(function (Location $location): void {
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

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

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
     * Update path without triggering model events.
     */
    public function updatePathWithoutSaving(): void
    {
        if (! $this->id) {
            return;
        }

        $newPath = $this->calculatePath();

        if ($this->path !== $newPath) {
            self::where('id', $this->id)->update(['path' => $newPath]);
            $this->path = $newPath;
            $this->updateChildrenPaths();
        }
    }

    /**
     * Calculate hierarchical path.
     */
    protected function calculatePath(): string
    {
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);

            if ($parent) {
                if (! $parent->path) {
                    $parent->updatePathWithoutSaving();
                }

                $siblings = self::where('parent_id', $this->parent_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->toArray();

                $siblingOrder = array_search($this->id, $siblings) + 1;

                return $parent->path . '/' . str_pad($siblingOrder, 4, '0', STR_PAD_LEFT);
            }

            return '/0001';
        }

        $roots = self::whereNull('parent_id')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $rootOrder = array_search($this->id, $roots) + 1;

        return '/' . str_pad($rootOrder, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update path (manual use).
     */
    public function updatePath(): void
    {
        $this->updatePathWithoutSaving();
    }

    /**
     * Update children paths recursively.
     */
    public function updateChildrenPaths(): void
    {
        foreach ($this->children as $child) {
            $child->updatePathWithoutSaving();
        }
    }

    /**
     * Get all locations ordered hierarchically.
     */
    public static function getHierarchicalOrder()
    {
        return self::orderBy('path')->get();
    }
}
