<?php

namespace App\Enums;

enum AdditionalOutputStatusType: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case ACCEPTED = 'accepted';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::UNDER_REVIEW => 'Dalam Review',
            self::ACCEPTED => 'Diterima',
            self::PUBLISHED => 'Terbit',
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
            self::UNDER_REVIEW => 'warning',
            self::ACCEPTED => 'success',
            self::PUBLISHED => 'primary',
            self::REJECTED => 'danger',
        };
    }
}
