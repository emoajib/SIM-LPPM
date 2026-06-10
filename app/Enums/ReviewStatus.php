<?php

namespace App\Enums;

/**
 * ReviewStatus Enum
 *
 * Represents the status of a reviewer's review on a proposal.
 */
enum ReviewStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case RE_REVIEW_REQUESTED = 're_review_requested';

    /**
     * Mendapatkan label dalam bahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Review',
            self::IN_PROGRESS => 'Sedang Direview',
            self::COMPLETED => 'Review Selesai',
            self::RE_REVIEW_REQUESTED => 'Perlu Review Ulang',
        };
    }

    /**
     * Mendapatkan warna badge untuk status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::RE_REVIEW_REQUESTED => 'orange',
        };
    }

    /**
     * Mendapatkan ikon untuk status
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::IN_PROGRESS => 'loader',
            self::COMPLETED => 'check-circle',
            self::RE_REVIEW_REQUESTED => 'refresh-cw',
        };
    }

    /**
     * Mendapatkan deskripsi status
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Review belum dimulai oleh reviewer',
            self::IN_PROGRESS => 'Reviewer sedang melakukan review',
            self::COMPLETED => 'Reviewer telah menyelesaikan review',
            self::RE_REVIEW_REQUESTED => 'Proposal telah direvisi, perlu review ulang',
        };
    }

    /**
     * Cek apakah status ini memerlukan aksi dari reviewer
     */
    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING, self::RE_REVIEW_REQUESTED]);
    }

    /**
     * Cek apakah status ini sudah selesai
     */
    public function isFinished(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Validasi apakah bisa transisi ke status baru
     */
    public function canTransitionTo(ReviewStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::IN_PROGRESS]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED]),
            self::COMPLETED => in_array($newStatus, [self::RE_REVIEW_REQUESTED]),
            self::RE_REVIEW_REQUESTED => in_array($newStatus, [self::IN_PROGRESS]),
        };
    }

    /**
     * Mendapatkan semua status yang bisa ditampilkan di filter
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return [
            'all' => 'Semua Status',
            self::PENDING->value => self::PENDING->label(),
            self::IN_PROGRESS->value => self::IN_PROGRESS->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::RE_REVIEW_REQUESTED->value => self::RE_REVIEW_REQUESTED->label(),
        ];
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
     * Mendapatkan status yang memerlukan aksi
     *
     * @return array<int, ReviewStatus>
     */
    public static function actionRequired(): array
    {
        return [self::PENDING, self::RE_REVIEW_REQUESTED];
    }
}
