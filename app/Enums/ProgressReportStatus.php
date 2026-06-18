<?php

namespace App\Enums;

enum ProgressReportStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::APPROVED => 'Disetujui',
        };
    }

    /**
     * Get badge color
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::APPROVED => 'success',
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
