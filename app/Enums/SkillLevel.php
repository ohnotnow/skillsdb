<?php

namespace App\Enums;

enum SkillLevel: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Low => 'amber',
            self::Medium => 'sky',
            self::High => 'green',
        };
    }
}
