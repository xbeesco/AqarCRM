<?php

namespace App\Enums;

enum UnitOccupancyStatus: string
{
    case VACANT = 'vacant';
    case OCCUPIED = 'occupied';

    public function label(): string
    {
        return match ($this) {
            self::VACANT => 'شاغرة',
            self::OCCUPIED => 'مشغولة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VACANT => 'success',
            self::OCCUPIED => 'danger',
        };
    }

    public static function fromUnit($unit): self
    {
        return $unit->activeContract()->exists() ? self::OCCUPIED : self::VACANT;
    }
}
