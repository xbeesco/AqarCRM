<?php

namespace App\Enums;

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
     * الحصول على الأيقونة
     */
    public function icon(): string
    {
        return match($this) {
            self::COLLECTED => 'heroicon-o-check-circle',
            self::DUE => 'heroicon-o-clock',
            self::POSTPONED => 'heroicon-o-pause-circle',
            self::OVERDUE => 'heroicon-o-exclamation-circle',
            self::UPCOMING => 'heroicon-o-calendar',
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
     * إنشاء من التسمية العربية
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