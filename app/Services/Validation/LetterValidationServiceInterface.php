<?php

namespace App\Services\Validation;

use App\Models\Letter;

interface LetterValidationServiceInterface
{
    /**
     * Validate letter creation business rules
     *
     * @param  array  $data  Letter creation data
     * @param  string|null  $userId  User ID creating the letter
     * @return array Validation errors, empty if valid
     */
    public function validateLetterCreation(array $data, ?string $userId = null): array;

    /**
     * Validate status transition
     *
     * @param  string  $currentStatus  Current letter status
     * @param  string  $newStatus  New letter status
     * @return array Validation errors
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus): array;

    /**
     * Validate signature mode transition
     *
     * @param  string  $currentSignatureMode  Current signature mode
     * @param  string  $newSignatureMode  New signature mode
     * @return array Validation errors
     */
    public function validateSignatureModeTransition(string $currentSignatureMode, string $newSignatureMode): array;

    /**
     * Validate letter approval
     *
     * @param  Letter  $letter  Letter to approve
     * @param  string  $approverId  Approver user ID
     * @return array Validation errors
     */
    public function validateLetterApproval(Letter $letter, string $approverId): array;

    /**
     * Validate letter rejection
     *
     * @param  Letter  $letter  Letter to reject
     * @param  string  $rejecterId  Rejecter user ID
     * @return array Validation errors
     */
    public function validateLetterRejection(Letter $letter, string $rejecterId): array;

    /**
     * Validate letter cancellation
     *
     * @param  Letter  $letter  Letter to cancel
     * @param  string  $cancelerId  Canceler user ID
     * @return array Validation errors
     */
    public function validateLetterCancellation(Letter $letter, string $cancelerId): array;

    /**
     * Validate letter resubmission
     *
     * @param  Letter  $letter  Letter to resubmit
     * @param  string  $resubmitterId  Resubmitter user ID
     * @return array Validation errors
     */
    public function validateLetterResubmission(Letter $letter, string $resubmitterId): array;

    /**
     * Validate letter export
     *
     * @param  Letter  $letter  Letter to export
     * @param  string  $exporterId  Exporter user ID
     * @return array Validation errors
     */
    public function validateLetterExport(Letter $letter, string $exporterId): array;
}
