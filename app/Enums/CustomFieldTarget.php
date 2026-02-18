<?php

namespace App\Enums;

enum CustomFieldTarget: string
{
    case Unit = 'unit';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'الوحدة',
            self::Property => 'العقار',
        };
    }
}
