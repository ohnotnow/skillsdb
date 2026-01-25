<?php

namespace App\Enums;

enum EnrollmentStatus: int
{
    case PendingApproval = 0;
    case Booked = 1;
    case Completed = 2;
    case Rejected = 3;

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::Booked => 'Booked',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::PendingApproval => 'sky',
            self::Booked => 'amber',
            self::Completed => 'green',
            self::Rejected => 'red',
        };
    }
}
