<?php

namespace App\Enums;

enum FluxColour: string
{
    case Zinc = 'zinc';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Green = 'green';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Sky = 'sky';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Purple = 'purple';
    case Fuchsia = 'fuchsia';
    case Pink = 'pink';
    case Rose = 'rose';

    public function label(): string
    {
        return match ($this) {
            self::Zinc => 'Zinc',
            self::Red => 'Red',
            self::Orange => 'Orange',
            self::Amber => 'Amber',
            self::Yellow => 'Yellow',
            self::Lime => 'Lime',
            self::Green => 'Green',
            self::Emerald => 'Emerald',
            self::Teal => 'Teal',
            self::Cyan => 'Cyan',
            self::Sky => 'Sky',
            self::Blue => 'Blue',
            self::Indigo => 'Indigo',
            self::Violet => 'Violet',
            self::Purple => 'Purple',
            self::Fuchsia => 'Fuchsia',
            self::Pink => 'Pink',
            self::Rose => 'Rose',
        };
    }

    public function bgClass(): string
    {
        return "bg-{$this->value}-500";
    }

    public function textClass(): string
    {
        return "text-{$this->value}-500";
    }
}
