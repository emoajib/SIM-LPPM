<?php

namespace App\Enums;

enum Theme: string
{
    case CLASSIC = 'classic';
    case MODERN = 'modern';

    public static function fromString(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::CLASSIC;
    }

    public function isModern(): bool
    {
        return $this === self::MODERN;
    }
}
