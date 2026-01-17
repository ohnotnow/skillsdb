<?php

namespace App\Enums;

enum EnrollmentStatus: int
{
    case Booked = 1;
    case Completed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::Completed => 'Completed',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Booked => 'amber',
            self::Completed => 'green',
        };
    }
}
