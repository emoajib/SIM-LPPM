<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case NEED_ASSIGNMENT = 'need_assignment';
    case APPROVED = 'approved';
    case WAITING_REVIEWER = 'waiting_reviewer';
    case UNDER_REVIEW = 'under_review';
    case REVIEWED = 'reviewed';
    case REVISION_NEEDED = 'revision_needed';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    /**
     * Mendapatkan label dalam bahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::NEED_ASSIGNMENT => 'Perlu Persetujuan Anggota',
            self::APPROVED => 'Disetujui Dekan',
            self::WAITING_REVIEWER => 'Menunggu Penugasan Reviewer',
            self::UNDER_REVIEW => 'Sedang Direview',
            self::REVIEWED => 'Review Selesai',
            self::REVISION_NEEDED => 'Perlu Revisi',
            self::COMPLETED => 'Selesai',
            self::REJECTED => 'Ditolak',
        };
    }

    /**
     * Mendapatkan warna badge untuk status
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::NEED_ASSIGNMENT => 'warning',
            self::APPROVED => 'success',
            self::WAITING_REVIEWER => 'indigo',
            self::UNDER_REVIEW => 'cyan',
            self::REVIEWED => 'orange',
            self::REVISION_NEEDED => 'yellow',
            self::COMPLETED => 'azure',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Mendapatkan array dari semua nilai enum
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($status) => $status->value, self::cases());
    }

    /**
     * Validasi apakah bisa transisi ke status baru
     */
    public function canTransitionTo(ProposalStatus $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::SUBMITTED]),
            self::SUBMITTED => in_array($newStatus, [self::APPROVED, self::NEED_ASSIGNMENT, self::REJECTED]),
            self::NEED_ASSIGNMENT => in_array($newStatus, [self::SUBMITTED]),
            self::APPROVED => in_array($newStatus, [self::WAITING_REVIEWER, self::UNDER_REVIEW, self::REJECTED]),
            self::WAITING_REVIEWER => in_array($newStatus, [self::UNDER_REVIEW]),
            self::UNDER_REVIEW => in_array($newStatus, [self::REVIEWED]),
            self::REVIEWED => in_array($newStatus, [self::COMPLETED, self::REVISION_NEEDED, self::REJECTED]),
            self::REVISION_NEEDED => in_array($newStatus, [self::SUBMITTED]),
            self::COMPLETED => false,
            self::REJECTED => false,
        };
    }

    /**
     * Mendapatkan semua status yang bisa ditampilkan di filter
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        $options = ['all' => 'Semua Status'];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * Cek apakah status ini menunjukkan proposal sudah selesai
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::REJECTED]);
    }

    /**
     * Cek apakah proposal bisa diedit oleh dosen
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::REVISION_NEEDED]);
    }
}
