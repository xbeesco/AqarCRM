<?php

namespace App\Services;

use App\Models\SupplyPayment;
use App\Models\Unit;
use Carbon\Carbon;

class ExpenseValidationService
{
    /**
     * Validate if an expense can be added for a property on a specific date.
     * Returns an error message if:
     * 1. No supply payment exists for the given period
     * 2. A supply payment exists but has already been paid
     */
    public function validateExpenseDate($propertyId, $date, $excludeExpenseId = null): ?string
    {
        if (! $propertyId || ! $date) {
            return null;
        }

        $expenseDate = Carbon::parse($date);

        // Find any supply payment in the same period (paid or unpaid)
        $supplyPayment = SupplyPayment::query()
            ->whereHas('propertyContract', function ($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            })
            ->where(function ($q) use ($expenseDate) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(invoice_details, '$.period_start')) <= ?", [$expenseDate->format('Y-m-d')])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(invoice_details, '$.period_end')) >= ?", [$expenseDate->format('Y-m-d')]);
            })
            ->first();

        // If no supply payment exists for this period
        if (! $supplyPayment) {
            return sprintf(
                'لا توجد دفعة مالك  تناسب هذا التاريخ لهذا العقار',
                $expenseDate->format('Y-m-d')
            );
        }

        // If supply payment exists and is already paid
        if ($supplyPayment->paid_date) {
            return sprintf(
                'دفعة المالك لهذه الفترة تم توريدها بالفعل',
                $expenseDate->format('Y-m-d'),
                $supplyPayment->payment_number
            );
        }

        // If supply payment exists and is not yet paid - allow adding expense
        return null;
    }

    /**
     * Validate if an expense can be added for a specific unit on a given date.
     * Gets the property from the unit and validates against supply payments.
     */
    public function validateExpenseDateForUnit($unitId, $date, $excludeExpenseId = null): ?string
    {
        if (! $unitId || ! $date) {
            return null;
        }

        // Get the property from the unit
        $unit = Unit::find($unitId);
        if (! $unit) {
            return 'الوحدة المختارة غير موجودة';
        }

        return $this->validateExpenseDate($unit->property_id, $date, $excludeExpenseId);
    }

    /**
     * Comprehensive expense validation based on type (property or unit).
     */
    public function validateExpense($expenseFor, $propertyId, $unitId, $date, $excludeExpenseId = null): ?string
    {
        if (! $expenseFor || ! $date) {
            return null;
        }

        switch ($expenseFor) {
            case 'property':
                return $this->validateExpenseDate($propertyId, $date, $excludeExpenseId);

            case 'unit':
                if (! $unitId) {
                    return 'يجب اختيار وحدة';
                }

                return $this->validateExpenseDateForUnit($unitId, $date, $excludeExpenseId);

            default:
                return 'نوع النفقة غير صحيح';
        }
    }

    /**
     * Check if an existing expense can be edited.
     */
    public function canEditExpense($expense): bool
    {
        if (! $expense) {
            return false;
        }

        // If expense is for a property
        if ($expense->subject_type === 'App\Models\Property') {
            $error = $this->validateExpenseDate($expense->subject_id, $expense->date, $expense->id);

            return $error === null;
        }
        // If expense is for a unit
        elseif ($expense->subject_type === 'App\Models\Unit') {
            $error = $this->validateExpenseDateForUnit($expense->subject_id, $expense->date, $expense->id);

            return $error === null;
        }

        return true;
    }
}
