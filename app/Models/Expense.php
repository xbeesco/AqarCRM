<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Expense extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعديل
     */
    protected $fillable = [
        'desc',
        'type',
        'cost',
        'date',
        'docs',
        'subject_type',
        'subject_id',
    ];

    /**
     * تحويل الحقول إلى أنواع البيانات المناسبة
     */
    protected $casts = [
        'cost' => 'decimal:2',
        'date' => 'date',
        'docs' => 'array',
    ];

    /**
     * العلاقة Polymorphic - الكيان المرتبط بالنفقة
     * يمكن أن يكون Property أو Unit
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * أنواع النفقات المتاحة
     */
    public const TYPES = [
        'maintenance' => 'صيانة',
        'government' => 'مصاريف حكومية',
        'purchases' => 'مشتريات',
        'utilities' => 'فواتير خدمات',
        'other' => 'أخرى',
    ];

    /**
     * ألوان الـ badges للأنواع المختلفة
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

    /**
     * الحصول على اسم النوع بالعربية
     */
    public function getTypeNameAttribute(): string
    {
        return static::TYPES[$this->type] ?? $this->type;
    }

    /**
     * الحصول على لون badge النوع
     */
    public function getTypeColorAttribute(): string
    {
        return static::TYPE_COLORS[$this->type] ?? 'gray';
    }

    /**
     * الحصول على عدد الإثباتات
     */
    public function getDocsCountAttribute(): int
    {
        return $this->docs ? count($this->docs) : 0;
    }

    /**
     * تنسيق المبلغ مع العملة
     */
    public function getFormattedCostAttribute(): string
    {
        return number_format((float) $this->cost, 2) . ' ريال';
    }

    /**
     * التحقق من وجود إثباتات
     */
    public function hasDocuments(): bool
    {
        return $this->docs_count > 0;
    }

    /**
     * الحصول على إثباتات من نوع معين
     */
    public function getDocumentsByType(string $type): array
    {
        if (!$this->docs) {
            return [];
        }

        return array_filter($this->docs, function ($doc) use ($type) {
            return isset($doc['type']) && $doc['type'] === $type;
        });
    }

    /**
     * حساب مجموع المبالغ من الإثباتات
     */
    public function calculateDocsTotal(): float
    {
        if (!$this->docs) {
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

    /**
     * Scope للفلترة حسب النوع
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope للفلترة حسب التاريخ
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope للنفقات في الشهر الحالي
     */
    public function scopeThisMonth($query)
    {
        $currentDate = Carbon::now();
        return $query->whereMonth('date', $currentDate->month)
                    ->whereYear('date', $currentDate->year);
    }

    /**
     * Scope للنفقات في السنة الحالية
     */
    public function scopeThisYear($query)
    {
        $currentDate = Carbon::now();
        return $query->whereYear('date', $currentDate->year);
    }

    /**
     * Scope للنفقات الخاصة بالعقارات فقط
     */
    public function scopeForProperties($query)
    {
        return $query->where('subject_type', 'App\Models\Property');
    }

    /**
     * Scope للنفقات الخاصة بالوحدات فقط
     */
    public function scopeForUnits($query)
    {
        return $query->where('subject_type', 'App\Models\Unit');
    }

    /**
     * Scope للنفقات الخاصة بعقار معين
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('subject_type', 'App\Models\Property')
                     ->where('subject_id', $propertyId);
    }

    /**
     * Scope للنفقات الخاصة بوحدة معينة
     */
    public function scopeForUnit($query, $unitId)
    {
        return $query->where('subject_type', 'App\Models\Unit')
                     ->where('subject_id', $unitId);
    }

    /**
     * الحصول على اسم الكيان المرتبط
     */
    public function getSubjectNameAttribute(): string
    {
        if (!$this->subject) {
            return 'غير محدد';
        }

        if ($this->subject_type === 'App\Models\Property') {
            return 'العقار: ' . $this->subject->name;
        }

        if ($this->subject_type === 'App\Models\Unit') {
            return 'الوحدة: ' . $this->subject->name . ' - ' . $this->subject->property->name;
        }

        return 'غير معروف';
    }
}