<?php

namespace App\Enums;

enum PolicyInvolvementStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
        };
    }

    /**
     * Get badge color
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Get all enum values as an array
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
