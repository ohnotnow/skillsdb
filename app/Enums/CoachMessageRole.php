<?php

namespace App\Enums;

enum CoachMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::User => 'You',
            self::Assistant => 'Coach',
        };
    }
}
