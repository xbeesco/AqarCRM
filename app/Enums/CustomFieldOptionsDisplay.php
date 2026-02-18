<?php

namespace App\Enums;

enum CustomFieldOptionsDisplay: string
{
    case Menu = 'menu';
    case Toggle = 'toggle';
    case Checkboxes = 'checkboxes';
    case MultiSelectMenu = 'multiselect';

    public function label(): string
    {
        return match ($this) {
            self::Menu => 'قائمة منسدلة (اختيار واحد)',
            self::Toggle => 'مفتاح تبديل',
            self::Checkboxes => 'خانات اختيار (متعدد)',
            self::MultiSelectMenu => 'قائمة منسدلة (متعدد)',
        };
    }
}
