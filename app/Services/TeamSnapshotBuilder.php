<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;

class TeamSnapshotBuilder
{
    public static function forProposal(Proposal $proposal): array
    {
        $team = [];
        $submitterName = $proposal->submitter->name;

        $team[] = [
            'name' => $submitterName,
            'role' => 'Ketua',
            'identifier' => $proposal->submitter->identity->identity_id ?? '-',
        ];

        foreach ($proposal->teamMembers as $member) {
            if ($member->name === $submitterName) {
                continue;
            }
            $pivot = $member->pivot;
            if ($pivot && $pivot->getAttribute('status') === 'accepted') {
                $team[] = [
                    'name' => $member->name,
                    'role' => 'Anggota',
                    'identifier' => $member->identity->identity_id ?? '-',
                ];
            }
        }

        if ($proposal->student_members) {
            foreach ($proposal->student_members as $student) {
                $team[] = [
                    'name' => $student['name'],
                    'role' => 'Mahasiswa',
                    'identifier' => $student['identifier'] ?? $student['nim'] ?? '-',
                ];
            }
        }

        return $team;
    }

    public static function forManual(array $userTeam, User $requester): array
    {
        return array_map(fn ($m) => [
            'name' => $m['name'],
            'role' => $m['role'],
            'identifier' => $m['identifier'] ?? '-',
        ], $userTeam);
    }

    public static function forProposalLinked(Proposal $proposal, array $manualTeam, User $requester): array
    {
        $team = array_map(fn ($m) => [
            'name' => $m['name'],
            'role' => $m['role'],
            'identifier' => $m['identifier'] ?? '-',
        ], $manualTeam);

        $requesterExists = false;
        foreach ($team as &$member) {
            if ($member['name'] === $requester->name) {
                $member['role'] = 'Ketua';
                $member['identifier'] = $requester->identity->identity_id ?? '-';
                $requesterExists = true;
                break;
            }
        }
        unset($member);

        if (! $requesterExists) {
            array_unshift($team, [
                'name' => $requester->name,
                'role' => 'Ketua',
                'identifier' => $requester->identity->identity_id ?? '-',
            ]);
        }

        return $team;
    }
}
