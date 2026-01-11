<?php

namespace App\Enums;

enum SkillHistoryEvent: string
{
    case Added = 'added';
    case Removed = 'removed';
    case LevelledUp = 'levelled_up';
    case LevelledDown = 'levelled_down';

    public function label(): string
    {
        return match ($this) {
            self::Added => 'Added',
            self::Removed => 'Removed',
            self::LevelledUp => 'Levelled Up',
            self::LevelledDown => 'Levelled Down',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Added => 'plus-circle',
            self::Removed => 'minus-circle',
            self::LevelledUp => 'arrow-up-circle',
            self::LevelledDown => 'arrow-down-circle',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Added => 'green',
            self::Removed => 'red',
            self::LevelledUp => 'sky',
            self::LevelledDown => 'amber',
        };
    }
}
