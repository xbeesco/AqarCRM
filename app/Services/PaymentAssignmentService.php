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
     * تحديد ما إذا كانت دفعة التحصيل تنتمي لفترة دفعة التوريد المحددة
     * Determine if a collection payment belongs to a specific supply payment period
     *
     * Business Logic:
     * 1. Early Payment: March rent paid in February -> belongs to March supply payment
     * 2. On-time Payment: March rent paid in March -> belongs to March supply payment
     * 3. Very Late Payment: February rent paid in April -> belongs to April supply payment
     */
    public function shouldPaymentBelongToPeriod(
        CollectionPayment $payment,
        string $periodStart,
        string $periodEnd
    ): bool {
        // 1. تحديد الفترة الأصلية للدفعة
        $paymentDueStart = $payment->due_date_start;
        $paymentOriginallyBelongsHere = $paymentDueStart >= $periodStart && $paymentDueStart <= $periodEnd;

        // 2. إذا لم تُدفع، تبقى مع الفترة الأصلية
        if (!$payment->paid_date) {
            return $paymentOriginallyBelongsHere;
        }

        // 3. إذا تم الدفع، تحقق من التأخير الشديد (عبور فترات دفعات التوريد)
        $paidDate = $payment->paid_date;

        // إذا تم الدفع في هذه الفترة، فهي تنتمي هنا بغض النظر عن تاريخ الاستحقاق الأصلي
        $paidInThisPeriod = $paidDate >= $periodStart && $paidDate <= $periodEnd;

        // 4. معالجة الحالات الثلاث:

        // الحالة أ: الدفع المبكر (الفترة الأصلية بعد هذه الفترة)
        // مثال: إيجار مارس دُفع في فبراير -> ينتمي لدفعة توريد مارس
        if ($paymentDueStart > $periodEnd) {
            return false; // الدفعات المبكرة تنتمي لفترتها الأصلية، وليس لهذه
        }

        // الحالة ب: الدفع العادي أو المتأخر العادي (الفترة الأصلية تطابق هذه الفترة)
        // مثال: إيجار فبراير دُفع في فبراير (أو متأخر قليلاً) -> ينتمي لدفعة توريد فبراير
        if ($paymentOriginallyBelongsHere) {
            return true; // الدفعة تنتمي لفترتها الأصلية
        }

        // الحالة ج: الدفع المتأخر جداً (الفترة الأصلية قبل هذه الفترة، لكن دُفعت في هذه الفترة)
        // مثال: إيجار يناير دُفع في مارس -> ينتمي لدفعة توريد مارس حيث تم التحصيل فعلياً
        if ($paymentDueStart < $periodStart && $paidInThisPeriod) {
            return true; // الدفعة المتأخرة جداً تُخصص للفترة التي تم دفعها فيها
        }

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
     * التحقق من حالة دفعة التحصيل بالنسبة للفترة
     * Check collection payment status relative to a period
     */
    public function getPaymentStatusForPeriod(
        CollectionPayment $payment,
        string $periodStart,
        string $periodEnd
    ): string {
        $paymentDueStart = $payment->due_date_start;

        if (!$payment->paid_date) {
            return 'unpaid';
        }

        $paidDate = $payment->paid_date;

        // دفع مبكر
        if ($paymentDueStart > $periodEnd && $paidDate < $periodStart) {
            return 'early_payment';
        }

        // دفع في الوقت أو متأخر عادي
        if ($paymentDueStart >= $periodStart && $paymentDueStart <= $periodEnd) {
            if ($paidDate >= $periodStart && $paidDate <= $periodEnd) {
                return 'on_time_payment';
            } else if ($paidDate > $periodEnd) {
                return 'late_payment';
            }
        }

        // دفع متأخر جداً
        if ($paymentDueStart < $periodStart && $paidDate >= $periodStart && $paidDate <= $periodEnd) {
            return 'very_late_payment';
        }

        return 'unassigned';
    }
}