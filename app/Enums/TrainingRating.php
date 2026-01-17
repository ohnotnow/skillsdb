<?php

namespace App\Enums;

enum TrainingRating: int
{
    case Bad = 1;
    case Indifferent = 2;
    case Good = 3;

    public function label(): string
    {
        return match ($this) {
            self::Bad => 'Bad',
            self::Indifferent => 'Indifferent',
            self::Good => 'Good',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Bad => 'red',
            self::Indifferent => 'zinc',
            self::Good => 'green',
        };
    }
}
