<?php

namespace App\Enums;

enum TeamSource: string
{
    case PROPOSAL = 'proposal';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSAL => 'Dari Proposal',
            self::MANUAL => 'Input Manual',
        };
    }
}
