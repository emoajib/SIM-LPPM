-- ============================================================
-- VALIDASI DATA INTEGRITY — Pasca Migrasi MySQL → PostgreSQL
-- Jalankan di pgAdmin 4 atau psql setelah migration selesai
-- ============================================================

-- 1. CEK ANOMALI proposals.status
-- Harusnya: hanya 11 nilai dari ProposalStatus enum
SELECT '1. proposals.status anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE status NOT IN (
           'draft','submitted','need_assignment','approved',
           'waiting_reviewer','under_review','reviewed',
           'revision_needed','revision_submitted','completed','rejected'
       )) as invalid_count
FROM proposals;
SELECT DISTINCT status FROM proposals ORDER BY status;

-- 2. CEK ANOMALI proposal_user.role
-- Harusnya: hanya 'ketua' atau 'anggota'
SELECT '2. proposal_user.role anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE role NOT IN ('ketua', 'anggota')) as invalid_count
FROM proposal_user;
SELECT DISTINCT role FROM proposal_user ORDER BY role;

-- 3. CEK ANOMALI proposal_user.status
-- Harusnya: hanya 'pending', 'accepted', 'rejected'
SELECT '3. proposal_user.status anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE status NOT IN ('pending', 'accepted', 'rejected')) as invalid_count
FROM proposal_user;
SELECT DISTINCT status FROM proposal_user ORDER BY status;

-- 4. CEK ANOMALI research_schemes.strata
-- Harusnya: hanya 'Dasar', 'Terapan', 'Pengembangan', 'PKM'
SELECT '4. research_schemes.strata anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE strata NOT IN ('Dasar', 'Terapan', 'Pengembangan', 'PKM')) as invalid_count
FROM research_schemes;
SELECT DISTINCT strata FROM research_schemes ORDER BY strata;

-- 5. CEK ANOMALI additional_outputs.status
-- Harusnya: hanya 6 nilai dari AdditionalOutputStatusType
SELECT '5. additional_outputs.status anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE status IS NOT NULL AND status NOT IN (
           'draft','submitted','under_review','accepted','published','rejected'
       )) as invalid_count
FROM additional_outputs;
SELECT DISTINCT status FROM additional_outputs ORDER BY status;

-- 6. CEK ANOMALI progress_reports.reporting_period
-- Harusnya: hanya 'semester_1', 'semester_2', 'annual', 'final'
SELECT '6. progress_reports.reporting_period anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE reporting_period NOT IN ('semester_1', 'semester_2', 'annual', 'final')) as invalid_count
FROM progress_reports;
SELECT DISTINCT reporting_period FROM progress_reports ORDER BY reporting_period;

-- 7. CEK ANOMALI letters.status
-- Harusnya: hanya 6 nilai dari LetterStatus
SELECT '7. letters.status anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE status NOT IN (
           'draft','pending_verification','pending_approval',
           'ready_to_print','published','rejected'
       )) as invalid_count
FROM letters;
SELECT DISTINCT status FROM letters ORDER BY status;

-- 8. CEK ANOMALI letters.signature_mode
-- Harusnya: hanya 'tte', 'manual', 'published', 'ready_to_print'
SELECT '8. letters.signature_mode anomaly' as check_name,
       COUNT(*) as total,
       COUNT(*) FILTER (WHERE signature_mode NOT IN ('tte', 'manual', 'published', 'ready_to_print')) as invalid_count
FROM letters;
SELECT DISTINCT signature_mode FROM letters ORDER BY signature_mode;

-- 9. CEK CHECK CONSTRAINT
-- Verifikasi bahwa semua CHECK constraint terdaftar
SELECT '9. missing check constraints' as check_name, COUNT(*) as total
FROM (
    SELECT 'proposal_user_role_check' as name
    UNION SELECT 'proposal_user_status_check'
    UNION SELECT 'proposals_status_check'
    UNION SELECT 'proposal_reviewer_status_check'
    UNION SELECT 'proposal_reviewer_recommendation_check'
    UNION SELECT 'progress_reports_status_check'
    UNION SELECT 'progress_reports_reporting_period_check'
    UNION SELECT 'identities_type_check'
    UNION SELECT 'additional_outputs_status_check'
    UNION SELECT 'mandatory_outputs_status_type_check'
    UNION SELECT 'institutional_reports_status_check'
    UNION SELECT 'proposal_kaprodi_approvals_status_check'
    UNION SELECT 'document_signatures_mode_check'
    UNION SELECT 'letters_status_check'
    UNION SELECT 'letters_signature_mode_check'
    UNION SELECT 'letters_team_source_check'
    UNION SELECT 'research_schemes_strata_check'
    UNION SELECT 'budget_groups_proposal_type_check'
    UNION SELECT 'budget_groups_percentage_type_check'
    UNION SELECT 'review_logs_recommendation_check'
    UNION SELECT 'monev_reviews_status_check'
    UNION SELECT 'sinta_score_submissions_status_check'
    UNION SELECT 'manual_books_status_check'
    UNION SELECT 'proposal_monevs_semester_check'
    UNION SELECT 'budget_caps_semester_check'
) as expected
LEFT JOIN information_schema.table_constraints tc
    ON tc.constraint_name = expected.name
    AND tc.constraint_schema = CURRENT_SCHEMA
WHERE tc.constraint_name IS NULL;

-- 10. CEK ORPHAN RECORDS (referensi ke data yang tidak ada)
SELECT '10. orphan proposal_user records' as check_name,
       COUNT(*) as total
FROM proposal_user pu
LEFT JOIN proposals p ON p.id = pu.proposal_id
WHERE p.id IS NULL;

SELECT '10b. orphan proposal_user user records' as check_name,
       COUNT(*) as total
FROM proposal_user pu
LEFT JOIN users u ON u.id = pu.user_id
WHERE u.id IS NULL;

-- 11. DUPLICATE KETUA (setiap proposal harus punya tepat 1 ketua)
SELECT '11. proposals with multiple ketua' as check_name,
       proposal_id, COUNT(*) as ketua_count
FROM proposal_user
WHERE role = 'ketua'
GROUP BY proposal_id
HAVING COUNT(*) > 1;

-- 12. PROPOSAL WITHOUT ketua
SELECT '12. proposals without ketua' as check_name,
       COUNT(*) as total
FROM proposals p
WHERE NOT EXISTS (
    SELECT 1 FROM proposal_user pu
    WHERE pu.proposal_id = p.id AND pu.role = 'ketua'
);
