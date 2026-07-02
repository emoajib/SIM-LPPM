<?php

return [
    'community_service_schemes' => [
        'id', 'name', 'strata', 'eligibility_rules', 'created_at', 'updated_at',
    ],
    'document_signatures' => [
        'id', 'document_type', 'document_id', 'variant', 'action', 'mode',
        'signed_role', 'signed_by', 'signed_at', 'hash_alg', 'document_hash',
        'kid', 'signature', 'payload', 'created_at', 'updated_at',
    ],
    'community_services' => [
        'id', 'macro_research_group_id', 'partner_id', 'partner_issue_summary',
        'solution_offered', 'created_at', 'updated_at', 'deleted_at',
    ],
    'faculties' => [
        'id', 'institution_id', 'name', 'dean_name', 'dean_id', 'dean_user_id',
        'research_roadmap', 'code', 'created_at', 'updated_at',
    ],
    'institutions' => [
        'id', 'name', 'is_default', 'code', 'type', 'is_verified', 'lppm_head_name',
        'lppm_head_id', 'lppm_head_user_id', 'short_name', 'address',
        'phone', 'email', 'website', 'created_at', 'updated_at',
    ],
    'letter_types' => [
        'id', 'code', 'name', 'description', 'category', 'numbering_format',
        'template_view', 'template_file_path', 'template_file_original_name',
        'template_file_size', 'template_uploaded_at', 'template_uploaded_by',
        'is_uploadable', 'is_active', 'created_at', 'updated_at', 'deleted_at',
    ],
    'national_priorities' => [
        'id', 'name', 'prn_code', 'valid_from', 'valid_until', 'description', 'created_at', 'updated_at',
    ],
    'research_schemes' => [
        'id', 'name', 'strata', 'min_tkt', 'max_tkt', 'eligibility_rules', 'description', 'created_at', 'updated_at',
    ],
    'proposal_outputs' => [
        'id', 'proposal_id', 'output_year', 'category', 'group', 'type', 'target_status', 'description', 'created_at', 'updated_at',
    ],
    'proposal_reviewer' => [
        'id', 'proposal_id', 'user_id', 'status', 'review_notes',
        'recommendation', 'round', 'assigned_at', 'deadline_at',
        'started_at', 'completed_at', 'created_at', 'updated_at',
    ],
    'review_logs' => [
        'id', 'proposal_reviewer_id', 'proposal_id', 'user_id', 'round',
        'review_notes', 'recommendation', 'total_score', 'started_at',
        'completed_at', 'created_at', 'updated_at',
    ],
    'proposals' => [
        'id', 'title', 'submitter_id', 'detailable_id', 'detailable_type',
        'research_scheme_id', 'focus_area_id', 'theme_id', 'topic_id', 'national_priority_id',
        'cluster_level1_id', 'cluster_level2_id', 'cluster_level3_id', 'sbk_value',
        'duration_in_years', 'start_year', 'semester', 'summary', 'asta_cita', 'status',
        'logbook_signed_at', 'student_members', 'created_at', 'updated_at', 'deleted_at',
        'community_service_scheme_id', 'qualification_snapshot', 'logbook_approved_at',
        'study_program_roadmap_id', 'bima_proposal_id', 'is_roadmap_validated_by_kaprodi',
        'kaprodi_validation_notes', 'kaprodi_validated_at', 'kaprodi_id',
    ],
    'review_criterias' => [
        'id', 'type', 'criteria', 'description', 'weight', 'order', 'is_active', 'created_at', 'updated_at',
    ],
    'study_programs' => [
        'id', 'institution_id', 'faculty_id', 'kaprodi_user_id', 'research_roadmap',
        'roadmap_status', 'name', 'code', 'created_at', 'updated_at',
    ],
    'budget_caps' => [
        'id', 'year', 'semester', 'research_budget_cap', 'community_service_budget_cap',
        'scheme_caps', 'enforce_percentage', 'created_at', 'updated_at',
    ],
    'budget_groups' => [
        'id', 'code', 'name', 'description', 'percentage', 'proposal_type',
        'percentage_type', 'is_active', 'created_at', 'updated_at',
    ],
    'iku_output_types' => [
        'id', 'name', 'group', 'is_active', 'created_at', 'updated_at',
    ],
    'master_ikus' => [
        'id', 'code', 'name', 'description', 'target_percentage', 'internal_weight',
        'is_active', 'created_at', 'updated_at',
    ],
];
