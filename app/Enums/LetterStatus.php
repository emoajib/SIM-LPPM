<?php

namespace App\Enums;

enum LetterStatus: string
{
    case DRAFT = 'draft';
    case PENDING_VERIFICATION = 'pending_verification';
    case PENDING_APPROVAL = 'pending_approval';
    case READY_TO_PRINT = 'ready_to_print';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_VERIFICATION => 'Menunggu Verifikasi',
            self::PENDING_APPROVAL => 'Menunggu Persetujuan',
            self::READY_TO_PRINT => 'Siap Cetak',
            self::PUBLISHED => 'Diterbitkan',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING_VERIFICATION => 'info',
            self::PENDING_APPROVAL => 'warning',
            self::READY_TO_PRINT => 'indigo',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
