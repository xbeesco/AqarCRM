<?php

namespace App\Enums;

use App\Models\CollectionPayment;
use App\Models\Setting;
use App\Helpers\DateHelper;

enum PaymentStatus: string
{
    case COLLECTED = 'collected';
    case DUE = 'due';
    case POSTPONED = 'postponed';
    case OVERDUE = 'overdue';
    case UPCOMING = 'upcoming';
    
    /**
     * الحصول على التسمية بالعربية
     */
    public function label(): string
    {
        return match($this) {
            self::COLLECTED => 'محصل',
            self::DUE => 'مستحق',
            self::POSTPONED => 'مؤجل',
            self::OVERDUE => 'متأخر',
            self::UPCOMING => 'قادم',
        };
    }
    
    /**
     * الحصول على لون البادج
     */
    public function color(): string
    {
        return match($this) {
            self::COLLECTED => 'success',
            self::DUE => 'warning',
            self::POSTPONED => 'info',
            self::OVERDUE => 'danger',
            self::UPCOMING => 'gray',
        };
    }
    
    /**
     * تحديد الحالة بناءً على بيانات الدفعة
     */
    public static function determineFor(CollectionPayment $payment): self
    {
        // إذا تم التحصيل
        if ($payment->collection_date) {
            return self::COLLECTED;
        }
        
        // إذا كانت مؤجلة
        if ($payment->delay_duration && $payment->delay_duration > 0) {
            return self::POSTPONED;
        }
        
        $today = DateHelper::getCurrentDate()->startOfDay();
        $paymentsDueDays = Setting::get('payment_due_days', 7);
        $overdueDate = $today->copy()->subDays($paymentsDueDays);
        
        // إذا كانت متأخرة (تجاوزت مدة السماح)
        if ($payment->due_date_start < $overdueDate) {
            return self::OVERDUE;
        }
        
        // إذا كانت مستحقة (وصل تاريخها لكن لم تتجاوز مدة السماح)
        if ($payment->due_date_start <= $today) {
            return self::DUE;
        }
        
        // إذا كانت قادمة (لم يصل تاريخها بعد)
        return self::UPCOMING;
    }
    
    /**
     * تطبيق فلتر الحالة على الاستعلام
     */
    public function applyToQuery($query)
    {
        $today = DateHelper::getCurrentDate()->startOfDay();
        $paymentsDueDays = Setting::get('payment_due_days', 7);
        $overdueDate = $today->copy()->subDays($paymentsDueDays);
        
        return match($this) {
            self::COLLECTED => $query->orWhereNotNull('collection_date'),
            
            self::POSTPONED => $query->orWhere(function($q) {
                $q->whereNull('collection_date')
                  ->whereNotNull('delay_duration')
                  ->where('delay_duration', '>', 0);
            }),
            
            self::OVERDUE => $query->orWhere(function($q) use ($overdueDate) {
                $q->whereNull('collection_date')
                  ->where('due_date_start', '<', $overdueDate)
                  ->where(function($innerQ) {
                      $innerQ->whereNull('delay_duration')
                             ->orWhere('delay_duration', 0);
                  });
            }),
            
            self::DUE => $query->orWhere(function($q) use ($today, $overdueDate) {
                $q->whereNull('collection_date')
                  ->where('due_date_start', '<=', $today)
                  ->where('due_date_start', '>=', $overdueDate)
                  ->where(function($innerQ) {
                      $innerQ->whereNull('delay_duration')
                             ->orWhere('delay_duration', 0);
                  });
            }),
            
            self::UPCOMING => $query->orWhere(function($q) use ($today) {
                $q->whereNull('collection_date')
                  ->where('due_date_start', '>', $today);
            }),
        };
    }
    
    /**
     * الحصول على كل الخيارات للفلاتر
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }
        return $options;
    }
    
    /**
     * إنشاء من قيمة نصية
     */
    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $status) {
            if ($status->label() === $label) {
                return $status;
            }
        }
        return null;
    }
}