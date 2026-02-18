<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Options = 'options';
    case Attachment = 'attachment';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'نص',
            self::Number => 'رقم',
            self::Options => 'خيارات',
            self::Attachment => 'مرفقات',
        };
    }
}
