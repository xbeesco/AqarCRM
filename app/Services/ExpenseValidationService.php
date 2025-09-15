<?php

namespace App\Services;

use App\Models\SupplyPayment;
use App\Models\Unit;
use Carbon\Carbon;

class ExpenseValidationService
{
    /**
     * التحقق من إمكانية إضافة نفقة في تاريخ معين لعقار
     * يُظهر خطأ إذا:
     * 1. لا توجد دفعة مالك في الفترة المحددة
     * 2. أو توجد دفعة مالك وتم دفعها بالفعل
     */
    public function validateExpenseDate($propertyId, $date, $excludeExpenseId = null): ?string
    {
        if (!$propertyId || !$date) {
            return null;
        }

        $expenseDate = Carbon::parse($date);

        // البحث عن أي دفعة مالك في نفس الفترة (مدفوعة أو غير مدفوعة)
        $supplyPayment = SupplyPayment::query()
            ->whereHas('propertyContract', function ($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            })
            ->where(function ($q) use ($expenseDate) {
                $q->whereRaw("JSON_EXTRACT(invoice_details, '$.period_start') <= ?", [$expenseDate->format('Y-m-d')])
                  ->whereRaw("JSON_EXTRACT(invoice_details, '$.period_end') >= ?", [$expenseDate->format('Y-m-d')]);
            })
            ->first();

        // إذا لم توجد دفعة أصلاً
        if (!$supplyPayment) {
            return sprintf(
                'لا توجد دفعة مالك  تناسب هذا التاريخ لهذا العقار',
                $expenseDate->format('Y-m-d')
            );
        }

        // إذا وُجدت دفعة وتم دفعها
        if ($supplyPayment->paid_date) {
            return sprintf(
                'دفعة المالك لهذه الفترة تم توريدها بالفعل',
                $expenseDate->format('Y-m-d'),
                $supplyPayment->payment_number
            );
        }

        // إذا وُجدت دفعة ولم يتم دفعها - السماح بإضافة النفقة
        return null;
    }

    /**
     * التحقق من إمكانية إضافة نفقة لوحدة معينة في تاريخ معين
     * يحصل على العقار من الوحدة ثم يتحقق من دفعات المالك
     */
    public function validateExpenseDateForUnit($unitId, $date, $excludeExpenseId = null): ?string
    {
        if (!$unitId || !$date) {
            return null;
        }

        // الحصول على العقار من الوحدة
        $unit = Unit::find($unitId);
        if (!$unit) {
            return 'الوحدة المختارة غير موجودة';
        }

        return $this->validateExpenseDate($unit->property_id, $date, $excludeExpenseId);
    }

    /**
     * التحقق الشامل للنفقة بناءً على نوعها (عقار أو وحدة)
     */
    public function validateExpense($expenseFor, $propertyId, $unitId, $date, $excludeExpenseId = null): ?string
    {
        if (!$expenseFor || !$date) {
            return null;
        }

        switch ($expenseFor) {
            case 'property':
                return $this->validateExpenseDate($propertyId, $date, $excludeExpenseId);

            case 'unit':
                if (!$unitId) {
                    return 'يجب اختيار وحدة';
                }
                return $this->validateExpenseDateForUnit($unitId, $date, $excludeExpenseId);

            default:
                return 'نوع النفقة غير صحيح';
        }
    }

    /**
     * التحقق من إمكانية تعديل نفقة موجودة
     */
    public function canEditExpense($expense): bool
    {
        if (!$expense) {
            return false;
        }

        // إذا كانت النفقة خاصة بعقار
        if ($expense->subject_type === 'App\Models\Property') {
            $error = $this->validateExpenseDate($expense->subject_id, $expense->date, $expense->id);
            return $error === null;
        }
        // إذا كانت النفقة خاصة بوحدة
        elseif ($expense->subject_type === 'App\Models\Unit') {
            $error = $this->validateExpenseDateForUnit($expense->subject_id, $expense->date, $expense->id);
            return $error === null;
        }

        return true;
    }
}