<?php

namespace App\Enums;

enum ProposalUserRole: string
{
    case KETUA = 'ketua';
    case ANGGOTA = 'anggota';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::KETUA => 'Ketua',
            self::ANGGOTA => 'Anggota',
        };
    }
}