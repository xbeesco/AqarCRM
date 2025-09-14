<?php

namespace App\Services;

use App\Models\CollectionPayment;
use Illuminate\Database\Eloquent\Collection;

/**
 * خدمة تخصيص دفعات التحصيل لدفعات التوريد
 * Payment Assignment Service for Collection Payments to Supply Payments
 */
class PaymentAssignmentService
{
    /**
     * تحديد ما إذا كانت دفعة التحصيل تُحتسب للمالك في فترة دفعة التوريد المحددة
     * Determine if a collection payment should be counted for owner in a specific supply payment period
     *
     * المنطق المحاسبي:
     * يُحاسب المالك فقط على المبالغ المحصلة فعلياً:
     * - النوع 2: دفعة للفترة ودُفعت خلالها ✅
     * - النوع 3: دفعة للفترة دُفعت قبلها (مدفوعة مسبقاً) ✅
     * - النوع 5: دفعة لفترة سابقة دُفعت خلال الفترة (متأخرات محصلة) ✅
     *
     * لا يُحاسب المالك على:
     * - النوع 1: دفعة للفترة لم تُدفع ❌
     * - النوع 4: دفعة للفترة دُفعت بعدها (ستُحتسب في الفترة التي دُفعت فيها) ❌
     * - النوع 6: دفعة لفترة لاحقة دُفعت مبكراً (ستُحتسب عند استحقاقها) ❌
     */
    public function shouldPaymentBelongToPeriod(
        CollectionPayment $payment,
        string $periodStart,
        string $periodEnd
    ): bool {
        // إذا لم تُدفع الدفعة، لا تُحتسب للمالك
        if (!$payment->paid_date) {
            return false;
        }

        $paymentDueStart = $payment->due_date_start->format('Y-m-d');
        $paidDate = $payment->paid_date->format('Y-m-d');

        // النوع 2: دفعة للفترة ودُفعت خلالها
        if ($paymentDueStart >= $periodStart && $paymentDueStart <= $periodEnd
            && $paidDate >= $periodStart && $paidDate <= $periodEnd) {
            return true;
        }

        // النوع 3: دفعة للفترة دُفعت قبلها (مدفوعة مسبقاً)
        if ($paymentDueStart >= $periodStart && $paymentDueStart <= $periodEnd
            && $paidDate < $periodStart) {
            return true;
        }

        // النوع 5: دفعة لفترة سابقة دُفعت خلال الفترة (متأخرات محصلة)
        if ($paymentDueStart < $periodStart
            && $paidDate >= $periodStart && $paidDate <= $periodEnd) {
            return true;
        }

        // الأنواع الأخرى لا تُحتسب
        return false;
    }

    /**
     * الحصول على دفعات التحصيل للفترة المحددة
     * Get collection payments for a specific period using business logic
     */
    public function getPaymentsForPeriod(
        int $propertyId,
        string $periodStart,
        string $periodEnd
    ): Collection {
        // جلب جميع دفعات التحصيل التي قد تنتمي لهذه الفترة
        $allPayments = CollectionPayment::with(['tenant', 'unit'])
            ->where('property_id', $propertyId)
            ->where(function($query) use ($periodStart, $periodEnd) {
                // جلب الدفعات التي:
                // 1. مستحقة أصلاً في هذه الفترة (due_date_start ضمن الفترة)
                // 2. أو مدفوعة في هذه الفترة (paid_date ضمن الفترة) - للدفعات المتأخرة جداً
                $query->whereBetween('due_date_start', [$periodStart, $periodEnd])
                      ->orWhereBetween('paid_date', [$periodStart, $periodEnd]);
            })
            ->orderBy('paid_date', 'desc')
            ->get();

        // تطبيق منطق الأعمال للفلترة
        return $allPayments->filter(function($payment) use ($periodStart, $periodEnd) {
            return $this->shouldPaymentBelongToPeriod($payment, $periodStart, $periodEnd);
        });
    }

    /**
     * حساب إجمالي المبالغ المحصلة للفترة
     * Calculate total amounts collected for a period
     */
    public function calculateCollectedAmountsForPeriod(
        int $propertyId,
        string $periodStart,
        string $periodEnd
    ): array {
        $payments = $this->getPaymentsForPeriod($propertyId, $periodStart, $periodEnd);

        $totalAmount = $payments->sum('total_amount');
        $paymentsCount = $payments->count();

        return [
            'total_amount' => $totalAmount,
            'payments_count' => $paymentsCount,
            'payments' => $payments
        ];
    }

    /**
     * تصنيف دفعة التحصيل حسب الأنواع الستة
     * Categorize collection payment into one of six types
     */
    public function getPaymentTypeForPeriod(
        CollectionPayment $payment,
        string $periodStart,
        string $periodEnd
    ): array {
        $paymentDueStart = $payment->due_date_start->format('Y-m-d');
        $paidDate = $payment->paid_date ? $payment->paid_date->format('Y-m-d') : null;

        // تحديد نوع الدفعة
        if ($paymentDueStart >= $periodStart && $paymentDueStart <= $periodEnd) {
            // دفعة تابعة للفترة
            if (!$paidDate) {
                return [
                    'type' => 1,
                    'name' => 'غير مدفوعة',
                    'counted' => false,
                    'color' => 'danger',
                    'icon' => 'heroicon-o-x-circle'
                ];
            } elseif ($paidDate >= $periodStart && $paidDate <= $periodEnd) {
                return [
                    'type' => 2,
                    'name' => 'مدفوعة',
                    'counted' => true,
                    'color' => 'success',
                    'icon' => 'heroicon-o-check-circle'
                ];
            } elseif ($paidDate < $periodStart) {
                return [
                    'type' => 3,
                    'name' => 'مدفوعة مسبقاً',
                    'counted' => true,
                    'color' => 'success',
                    'icon' => 'heroicon-o-clock'
                ];
            } else { // $paidDate > $periodEnd
                return [
                    'type' => 4,
                    'name' => 'دفعت لاحقا',
                    'counted' => false,
                    'color' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle'
                ];
            }
        } elseif ($paymentDueStart < $periodStart && $paidDate && $paidDate >= $periodStart && $paidDate <= $periodEnd) {
            return [
                'type' => 5,
                'name' => 'متأخرات محصلة',
                'counted' => true,
                'color' => 'success',
                'icon' => 'heroicon-o-arrow-path'
            ];
        } elseif ($paymentDueStart > $periodEnd && $paidDate && $paidDate >= $periodStart && $paidDate <= $periodEnd) {
            return [
                'type' => 6,
                'name' => 'دفعة مستقبلية',
                'counted' => false,
                'color' => 'gray',
                'icon' => 'heroicon-o-forward'
            ];
        }

        return [
            'type' => 0,
            'name' => 'غير مصنف',
            'counted' => false,
            'color' => 'gray',
            'icon' => 'heroicon-o-question-mark-circle'
        ];
    }

    /**
     * الحصول على دفعات التحصيل مصنفة حسب الأنواع الستة
     * Get collection payments categorized by the six types
     */
    public function getCategorizedPaymentsForPeriod(
        int $propertyId,
        string $periodStart,
        string $periodEnd
    ): array {
        // جلب جميع الدفعات المحتملة
        $allPayments = CollectionPayment::with(['tenant', 'unit'])
            ->where('property_id', $propertyId)
            ->where(function($query) use ($periodStart, $periodEnd) {
                // دفعات الفترة (مدفوعة أو غير مدفوعة)
                $query->whereBetween('due_date_start', [$periodStart, $periodEnd])
                    // أو دفعات مدفوعة في الفترة (بغض النظر عن تاريخ الاستحقاق)
                    ->orWhereBetween('paid_date', [$periodStart, $periodEnd]);
            })
            ->orderBy('due_date_start')
            ->orderBy('paid_date')
            ->get();

        // تصنيف الدفعات
        $categorized = [
            1 => ['name' => 'دفعات الفترة غير المدفوعة', 'payments' => collect(), 'total' => 0, 'counted' => false],
            2 => ['name' => 'دفعات الفترة المدفوعة خلالها', 'payments' => collect(), 'total' => 0, 'counted' => true],
            3 => ['name' => 'دفعات الفترة المدفوعة مسبقاً', 'payments' => collect(), 'total' => 0, 'counted' => true],
            4 => ['name' => 'دفعات الفترة المدفوعة متأخراً', 'payments' => collect(), 'total' => 0, 'counted' => false],
            5 => ['name' => 'متأخرات من فترات سابقة محصلة', 'payments' => collect(), 'total' => 0, 'counted' => true],
            6 => ['name' => 'دفعات مستقبلية محصلة مبكراً', 'payments' => collect(), 'total' => 0, 'counted' => false],
        ];

        foreach ($allPayments as $payment) {
            $typeInfo = $this->getPaymentTypeForPeriod($payment, $periodStart, $periodEnd);
            if ($typeInfo['type'] > 0) {
                $payment->type_info = $typeInfo;
                $categorized[$typeInfo['type']]['payments']->push($payment);
                $categorized[$typeInfo['type']]['total'] += $payment->total_amount;
            }
        }

        // حساب المجاميع
        $countedTotal = 0;
        $uncountedTotal = 0;
        foreach ($categorized as $category) {
            if ($category['counted']) {
                $countedTotal += $category['total'];
            } else {
                $uncountedTotal += $category['total'];
            }
        }

        return [
            'categories' => $categorized,
            'counted_total' => $countedTotal,
            'uncounted_total' => $uncountedTotal,
            'all_payments' => $allPayments
        ];
    }
}