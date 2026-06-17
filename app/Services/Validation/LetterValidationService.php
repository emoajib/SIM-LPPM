<?php

namespace App\Services\Validation;

use App\Enums\SignatureMode;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Proposal;

class LetterValidationService implements LetterValidationServiceInterface
{
    /**
     * Validate letter creation business rules
     *
     * @param  array  $data  Letter creation data
     * @param  string|null  $userId  User ID creating the letter
     * @return array Validation errors, empty if valid
     */
    public function validateLetterCreation(array $data, ?string $userId = null): array
    {
        $errors = [];

        // Validate letter type and proposal compatibility
        if (isset($data['letterTypeId']) && isset($data['referenceType'])) {
            $letterTypeErrors = $this->validateLetterTypeProposalCompatibility(
                $data['letterTypeId'],
                $data['referenceType'],
                $data['referenceId'] ?? null
            );
            $errors = array_merge($errors, $letterTypeErrors);
        }

        // Validate signature mode compatibility
        if (isset($data['signatureMode']) && isset($data['letterTypeId'])) {
            $signatureModeErrors = $this->validateSignatureModeCompatibility(
                $data['signatureMode'],
                $data['letterTypeId']
            );
            $errors = array_merge($errors, $signatureModeErrors);
        }

        // Validate team data integrity
        if (isset($data['team']) && ! empty($data['team'])) {
            $teamErrors = $this->validateTeamData($data['team'], $userId);
            $errors = array_merge($errors, $teamErrors);
        }

        // Validate duplicate prevention
        if (isset($data['letterTypeId']) && isset($data['referenceType']) && isset($data['referenceId'])) {
            $duplicateErrors = $this->validateDuplicatePrevention(
                $data['letterTypeId'],
                $data['referenceType'],
                $data['referenceId'],
                $userId
            );
            $errors = array_merge($errors, $duplicateErrors);
        }

        return $errors;
    }

    /**
     * Validate letter type and proposal compatibility
     *
     * @param  string  $letterTypeId  Letter type ID
     * @param  string  $referenceType  Reference type
     * @param  string|null  $referenceId  Reference ID
     * @return array Validation errors
     */
    private function validateLetterTypeProposalCompatibility(
        string $letterTypeId,
        string $referenceType,
        ?string $referenceId = null
    ): array {
        $errors = [];

        // Check if letter type exists and is active
        $letterType = LetterType::find($letterTypeId);
        if (! $letterType || ! $letterType->is_active) {
            $errors['letterTypeId'] = ['Letter type not found or is inactive.'];

            return $errors;
        }

        // Validate reference type compatibility
        if ($referenceType === 'App\\Models\\Proposal' && $referenceId) {
            $proposal = Proposal::find($referenceId);
            if (! $proposal) {
                $errors['referenceId'] = ['Proposal not found.'];
            } elseif ($proposal->submitter_id !== auth()->id()) {
                $errors['referenceId'] = ['You can only create letters for your own proposals.'];
            }
        }

        return $errors;
    }

    /**
     * Validate signature mode compatibility
     *
     * @param  string  $signatureMode  Signature mode
     * @param  string  $letterTypeId  Letter type ID
     * @return array Validation errors
     */
    private function validateSignatureModeCompatibility(
        string $signatureMode,
        string $letterTypeId
    ): array {
        $errors = [];

        // Validate signature mode enum
        if (! in_array($signatureMode, SignatureMode::getValues())) {
            $errors['signatureMode'] = ['Invalid signature mode.'];

            return $errors;
        }

        // Check if letter type supports the signature mode
        $letterType = LetterType::find($letterTypeId);
        if ($letterType && $signatureMode === SignatureMode::MANUAL->value && ! $letterType->supports_manual_signature) {
            $errors['signatureMode'] = ['This letter type does not support manual signatures.'];
        }

        return $errors;
    }

    /**
     * Validate team data integrity
     *
     * @param  array  $team  Team data
     * @param  string|null  $userId  User ID
     * @return array Validation errors
     */
    private function validateTeamData(array $team, ?string $userId = null): array
    {
        $errors = [];

        if (empty($team)) {
            $errors['team'] = ['Team must have at least one member.'];

            return $errors;
        }

        // Validate team member structure
        foreach ($team as $index => $member) {
            if (! isset($member['id']) || ! isset($member['name']) || ! isset($member['role'])) {
                $errors['team.'.$index] = ['Team member must have id, name, and role.'];

                continue;
            }

            // Validate role
            if (! in_array($member['role'], ['Ketua', 'Anggota'])) {
                $errors['team.'.$index.'.role'] = ['Role must be either Ketua or Anggota.'];
            }

            // Ensure user is in team as Ketua
            if ($userId && $member['role'] === 'Ketua' && $member['id'] !== $userId) {
                $errors['team.'.$index.'.role'] = ['Ketua must be the current user.'];
            }
        }

        return $errors;
    }

    /**
     * Validate duplicate prevention
     *
     * @param  string  $letterTypeId  Letter type ID
     * @param  string  $referenceType  Reference type
     * @param  string  $referenceId  Reference ID
     * @param  string|null  $userId  User ID
     * @return array Validation errors
     */
    private function validateDuplicatePrevention(
        string $letterTypeId,
        string $referenceType,
        string $referenceId,
        ?string $userId = null
    ): array {
        $errors = [];

        if (! $userId) {
            return $errors;
        }

        // Check for existing letters
        $existingLetter = Letter::where('user_id', $userId)
            ->where('letter_type_id', $letterTypeId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('status', ['pending_approval', 'published', 'ready_to_print'])
            ->first();

        if ($existingLetter) {
            $errors['duplicate'] = [
                'A letter of this type already exists for this proposal.',
                'Letter ID: '.$existingLetter->id,
                'Status: '.$existingLetter->status,
            ];
        }

        return $errors;
    }

    /**
     * Validate status transition
     *
     * @param  string  $currentStatus  Current letter status
     * @param  string  $newStatus  New letter status
     * @return array Validation errors
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus): array
    {
        $errors = [];

        // Check if current status is immutable
        if (in_array($currentStatus, Letter::STATUS_IMMUTABLE) && $currentStatus !== $newStatus) {
            $errors['status'] = [
                'Cannot transition from immutable status.',
                'Current status: '.$currentStatus,
                'New status: '.$newStatus,
            ];
        }

        // Validate status transition rules
        $validTransitions = $this->getValidStatusTransitions($currentStatus);
        if (! in_array($newStatus, $validTransitions)) {
            $errors['status'] = [
                'Invalid status transition.',
                'Current status: '.$currentStatus,
                'New status: '.$newStatus,
                'Valid transitions: '.implode(', ', $validTransitions),
            ];
        }

        return $errors;
    }

    /**
     * Get valid status transitions for a given status
     *
     * @param  string  $currentStatus  Current status
     * @return array Valid status transitions
     */
    private function getValidStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending_approval' => ['published', 'ready_to_print', 'rejected', 'cancelled'],
            'published' => [], // Immutable
            'ready_to_print' => [], // Immutable
            'rejected' => ['pending_approval'],
            'cancelled' => [], // Cannot be reactivated
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Validate signature mode transition
     *
     * @param  string  $currentSignatureMode  Current signature mode
     * @param  string  $newSignatureMode  New signature mode
     * @return array Validation errors
     */
    public function validateSignatureModeTransition(
        string $currentSignatureMode,
        string $newSignatureMode
    ): array {
        $errors = [];

        // Cannot change signature mode for published letters
        if ($currentSignatureMode === SignatureMode::PUBLISHED->value || $currentSignatureMode === SignatureMode::READY_TO_PRINT->value) {
            $errors['signatureMode'] = [
                'Cannot change signature mode for published letters.',
                'Current signature mode: '.$currentSignatureMode,
                'New signature mode: '.$newSignatureMode,
            ];
        }

        return $errors;
    }

    /**
     * Validate letter approval
     *
     * @param  Letter  $letter  Letter to approve
     * @param  string  $approverId  Approver user ID
     * @return array Validation errors
     */
    public function validateLetterApproval(Letter $letter, string $approverId): array
    {
        $errors = [];

        // Check if letter is in pending_approval status
        if ($letter->status !== 'pending_approval') {
            $errors['approval'] = [
                'Letter is not in pending_approval status.',
                'Current status: '.$letter->status,
            ];
        }

        // Check if approver has permission
        if ($letter->signature_mode === SignatureMode::MANUAL->value) {
            // For manual signatures, only kepala lppm can approve
            if (! auth()->user()->hasRole('kepala lppm') && ! auth()->user()->hasRole('rektor')) {
                $errors['approval'] = [
                    'Only kepala lppm or rektor can approve letters with manual signatures.',
                    'Your role: '.auth()->user()->getRoleNames()->implode(', '),
                ];
            }
        } else {
            // For digital signatures, any admin can approve
            if (! auth()->user()->hasRole('admin lppm') && ! auth()->user()->hasRole('superadmin') && ! auth()->user()->hasRole('kepala lppm') && ! auth()->user()->hasRole('rektor')) {
                $errors['approval'] = [
                    'Only admin lppm, superadmin, kepala lppm, or rektor can approve letters with digital signatures.',
                    'Your role: '.auth()->user()->getRoleNames()->implode(', '),
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate letter rejection
     *
     * @param  Letter  $letter  Letter to reject
     * @param  string  $rejecterId  Rejecter user ID
     * @return array Validation errors
     */
    public function validateLetterRejection(Letter $letter, string $rejecterId): array
    {
        $errors = [];

        // Check if letter is in pending_approval status
        if ($letter->status !== 'pending_approval') {
            $errors['rejection'] = [
                'Letter is not in pending_approval status.',
                'Current status: '.$letter->status,
            ];
        }

        // Check if rejecter has permission
        if (! auth()->user()->hasRole('kepala lppm') && ! auth()->user()->hasRole('rektor')) {
            $errors['rejection'] = [
                'Only kepala lppm or rektor can reject letters.',
                'Your role: '.auth()->user()->getRoleNames()->implode(', '),
            ];
        }

        return $errors;
    }

    /**
     * Validate letter cancellation
     *
     * @param  Letter  $letter  Letter to cancel
     * @param  string  $cancelerId  Canceler user ID
     * @return array Validation errors
     */
    public function validateLetterCancellation(Letter $letter, string $cancelerId): array
    {
        $errors = [];

        // Check if letter can be cancelled
        if (! in_array($letter->status, ['pending_approval', 'rejected'])) {
            $errors['cancellation'] = [
                'Letter cannot be cancelled in current status.',
                'Current status: '.$letter->status,
                'Allowed statuses: pending_approval, rejected',
            ];
        }

        // Check if canceler is the owner
        if ($letter->user_id !== $cancelerId) {
            $errors['cancellation'] = [
                'Only the letter owner can cancel the letter.',
                'Letter owner: '.$letter->user_id,
                'Canceler: '.$cancelerId,
            ];
        }

        return $errors;
    }

    /**
     * Validate letter resubmission
     *
     * @param  Letter  $letter  Letter to resubmit
     * @param  string  $resubmitterId  Resubmitter user ID
     * @return array Validation errors
     */
    public function validateLetterResubmission(Letter $letter, string $resubmitterId): array
    {
        $errors = [];

        // Check if letter is rejected
        if ($letter->status !== 'rejected') {
            $errors['resubmission'] = [
                'Letter is not in rejected status.',
                'Current status: '.$letter->status,
            ];
        }

        // Check if resubmitter is the owner
        if ($letter->user_id !== $resubmitterId) {
            $errors['resubmission'] = [
                'Only the letter owner can resubmit the letter.',
                'Letter owner: '.$letter->user_id,
                'Resubmitter: '.$resubmitterId,
            ];
        }

        return $errors;
    }

    /**
     * Validate letter export
     *
     * @param  Letter  $letter  Letter to export
     * @param  string  $exporterId  Exporter user ID
     * @return array Validation errors
     */
    public function validateLetterExport(Letter $letter, string $exporterId): array
    {
        $errors = [];

        // Check if letter is published or ready_to_print
        if (! in_array($letter->status, ['published', 'ready_to_print'])) {
            $errors['export'] = [
                'Letter is not in published or ready_to_print status.',
                'Current status: '.$letter->status,
            ];
        }

        // Check if exporter has permission
        if ($letter->user_id !== $exporterId &&
            ! auth()->user()->hasRole('admin lppm') &&
            ! auth()->user()->hasRole('superadmin') &&
            ! auth()->user()->hasRole('kepala lppm') &&
            ! auth()->user()->hasRole('rektor')) {
            $errors['export'] = [
                'Only the letter owner, admin lppm, superadmin, kepala lppm, or rektor can export the letter.',
                'Letter owner: '.$letter->user_id,
                'Exporter: '.$exporterId,
            ];
        }

        return $errors;
    }
}
