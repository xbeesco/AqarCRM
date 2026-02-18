<?php

namespace App\Support;

class CustomFieldSections
{
    /**
     * @return array<string, string>
     */
    public static function forTarget(string $target): array
    {
        return match ($target) {
            'unit' => [
                'basic_info' => 'المعلومات الأساسية',
                'details' => 'التفاصيل',
                'plans_notes' => 'المخططات والملاحظات',
            ],
            'property' => [
                'basic_data' => 'البيانات الأساسية',
                'location_address' => 'الموقع والعنوان',
                'additional_details' => 'تفاصيل إضافية',
            ],
            default => [],
        };
    }

    public static function label(string $target, string $section): string
    {
        return static::forTarget($target)[$section] ?? $section;
    }
}
