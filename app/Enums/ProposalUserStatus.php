<?php

namespace App\Enums;

enum ProposalUserStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::ACCEPTED => 'Diterima',
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
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
