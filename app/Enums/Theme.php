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

    public function toggle(): self
    {
        return $this === self::MODERN ? self::CLASSIC : self::MODERN;
    }

    /**
     * Label yang ditampilkan ke pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::CLASSIC => 'Klasik (Standar Tabler)',
            self::MODERN => 'Modern (3D Infografis)',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Validasi nilai mentah; null bila bukan nilai tema yang sah.
     */
    public static function tryFromString(?string $value): ?self
    {
        return self::tryFrom((string) $value);
    }
}
