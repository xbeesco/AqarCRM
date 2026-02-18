<?php

namespace App\Enums;

enum CustomFieldTextSubType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Email = 'email';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'نص قصير',
            self::Textarea => 'نص طويل',
            self::Email => 'بريد إلكتروني',
            self::Link => 'رابط',
        };
    }
}
