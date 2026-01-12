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

    public function bgClass(): string
    {
        return match ($this) {
            self::Low => 'bg-zinc-200 dark:bg-zinc-600',
            self::Medium => 'bg-sky-200 dark:bg-sky-700',
            self::High => 'bg-green-200 dark:bg-green-700',
        };
    }
}
