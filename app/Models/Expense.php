<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'desc',
        'type',
        'cost',
        'date',
        'docs',
        'subject_type',
        'subject_id',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date' => 'date',
        'docs' => 'array',
    ];

    /**
     * Available expense types with Arabic labels.
     */
    public const TYPES = [
        'maintenance' => 'صيانة',
        'government' => 'مصاريف حكومية',
        'purchases' => 'مشتريات',
        'utilities' => 'فواتير خدمات',
        'other' => 'أخرى',
    ];

    /**
     * Color codes for each expense type used in UI badges.
     */
    public const TYPE_COLORS = [
        'maintenance' => 'warning',
        'government' => 'danger',
        'utilities' => 'info',
        'purchases' => 'primary',
        'salaries' => 'success',
        'commissions' => 'secondary',
        'other' => 'gray',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeNameAttribute(): string
    {
        return static::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return static::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getDocsCountAttribute(): int
    {
        return $this->docs ? count($this->docs) : 0;
    }

    public function getFormattedCostAttribute(): string
    {
        return number_format((float) $this->cost, 2).' ريال';
    }

    public function hasDocuments(): bool
    {
        return $this->docs_count > 0;
    }

    public function getDocumentsByType(string $type): array
    {
        if (! $this->docs) {
            return [];
        }

        return array_filter($this->docs, function ($doc) use ($type) {
            return isset($doc['type']) && $doc['type'] === $type;
        });
    }

    public function calculateDocsTotal(): float
    {
        if (! $this->docs) {
            return 0;
        }

        $total = 0;
        foreach ($this->docs as $doc) {
            if (isset($doc['amount'])) {
                $total += (float) $doc['amount'];
            } elseif (isset($doc['hours']) && isset($doc['rate'])) {
                $total += (float) $doc['hours'] * (float) $doc['rate'];
            }
        }

        return $total;
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        $currentDate = Carbon::now();

        return $query->whereMonth('date', $currentDate->month)
            ->whereYear('date', $currentDate->year);
    }

    public function scopeThisYear($query)
    {
        $currentDate = Carbon::now();

        return $query->whereYear('date', $currentDate->year);
    }

    public function scopeForProperties($query)
    {
        return $query->where('subject_type', 'App\Models\Property');
    }

    public function scopeForUnits($query)
    {
        return $query->where('subject_type', 'App\Models\Unit');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('subject_type', 'App\Models\Property')
            ->where('subject_id', $propertyId);
    }

    public function scopeForUnit($query, $unitId)
    {
        return $query->where('subject_type', 'App\Models\Unit')
            ->where('subject_id', $unitId);
    }

    /**
     * Get the display name of the expense subject (Property or Unit).
     * Returns Arabic labels for display in the UI.
     */
    public function getSubjectNameAttribute(): string
    {
        if (! $this->subject) {
            return 'غير محدد'; // Not specified
        }

        if ($this->subject_type === 'App\Models\Property') {
            return 'العقار: '.$this->subject->name; // Property: {name}
        }

        if ($this->subject_type === 'App\Models\Unit') {
            return 'الوحدة: '.$this->subject->name.' - '.$this->subject->property->name; // Unit: {name} - {property}
        }

        return 'غير معروف'; // Unknown
    }
}
