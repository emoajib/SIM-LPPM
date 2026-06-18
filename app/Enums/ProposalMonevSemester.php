<?php

namespace App\Enums;

enum ProposalMonevSemester: string
{
    case GANJIL = 'ganjil';
    case GENAP = 'genap';

    /**
     * Get label in Indonesian
     */
    public function label(): string
    {
        return match ($this) {
            self::GANJIL => 'Ganjil',
            self::GENAP => 'Genap',
        };
    }
}