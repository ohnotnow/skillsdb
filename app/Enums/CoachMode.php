<?php

namespace App\Enums;

enum CoachMode: string
{
    case Personal = 'personal';
    case Team = 'team';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal Coach',
            self::Team => 'Team Coach',
        };
    }
}
