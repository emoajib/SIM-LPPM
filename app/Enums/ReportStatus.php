<?php

namespace App\Enums;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED_BY_DEKAN = 'approved_by_dekan';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::APPROVED_BY_DEKAN => 'Disetujui Dekan',
            self::APPROVED => 'Disetujui (Selesai)',
            self::REJECTED => 'Ditolak',
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
            self::APPROVED_BY_DEKAN => 'primary',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Check if transition is valid
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::SUBMITTED]),
            self::SUBMITTED => in_array($newStatus, [self::APPROVED_BY_DEKAN, self::REJECTED]),
            self::APPROVED_BY_DEKAN => in_array($newStatus, [self::APPROVED, self::REJECTED]),
            self::APPROVED => false,
            self::REJECTED => false,
        };
    }

    /**
     * Get descriptive labels for filters
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($status) => [
            $status->value => $status->label(),
        ])->toArray();
    }
}
