# Peta Kodebase (dibuat otomatis)

> Dihasilkan oleh `php artisan codebase:map`. **Jangan edit manual** —
> jalankan ulang perintahnya setelah menambah route/komponen/test.
> Dibuat: 2026-11-18

## Halaman modern (React)

| Route | Komponen (PAGES) |
|---|---|
| `research.proposal.index` | `proposals-index` |
| `research.proposal-revision.index` | `research-revision-index` |
| `research.final-report.index` | `research-final-report-index` |
| `research.daily-note.index` | `research-daily-note-index` |
| `community-service.proposal.index` | `pengabdian-index` |
| `community-service.proposal-revision.index` | `pengabdian-revision-index` |
| `community-service.final-report.index` | `pengabdian-final-report-index` |
| `community-service.daily-note.index` | `pengabdian-daily-note-index` |
| `research.verifikasi-luaran.page` | `verifikasi-luaran-index` |
| `community-service.verifikasi-luaran.page` | `verifikasi-luaran-index` |
| `research.daily-note.modern` | `daily-note-index` |
| `research.proposal.show.modern` | `proposal-show-index` |
| `community-service.daily-note.modern` | `daily-note-index` |
| `community-service.proposal.show.modern` | `proposal-show-index` |
| `settings.notification-config.page` | `notification-config-index` |
| `settings.profile.page` | `profile-index` |
| `settings.index.page` | `settings-index` |
| `settings.password.page` | `password-index` |
| `settings.feature-flags.page` | `feature-flags-index` |
| `settings.captcha.page` | `captcha-index` |

## Route per modul (jumlah)

| Prefix | Jumlah route |
|---|---|
| `research` | 36 |
| `community-service` | 33 |
| `settings` | 21 |
| `api` | 19 |
| `admin-lppm` | 17 |
| `admin` | 10 |
| `flux` | 9 |
| `livewire-a7760ddc` | 9 |
| `users` | 7 |
| `kepala-lppm` | 7 |
| `_debugbar` | 6 |
| `user` | 4 |
| `review` | 4 |
| `laporan-penelitian` | 3 |
| `laporan-pkm` | 3 |
| `laporan-luaran` | 3 |
| `laporan-mitra` | 3 |
| `reports` | 3 |
| `export-sinta` | 3 |
| `dekan` | 3 |
| `dosen` | 3 |
| `verify` | 3 |
| `login` | 2 |
| `captcha` | 2 |
| `laporan-monev` | 2 |
| `iku` | 2 |
| `kaprodi` | 2 |
| `letters` | 2 |
| `proposals` | 2 |
| `financial-reports` | 2 |
| `monev` | 2 |
| `verify-email` | 2 |
| `two-factor-challenge` | 2 |
| `storage` | 2 |
| `_boost` | 1 |
| `logout` | 1 |
| `up` | 1 |
| `install` | 1 |
| `health-check` | 1 |
| `/` | 1 |
| `dashboard` | 1 |
| `laporan-reviewer` | 1 |
| `recognition` | 1 |
| `surat` | 1 |
| `livewire` | 1 |
| `notifications` | 1 |
| `role` | 1 |
| `theme` | 1 |
| `reviewers` | 1 |
| `daily-notes` | 1 |
| `media` | 1 |
| `forgot-password` | 1 |
| `reset-password` | 1 |
| `confirm-password` | 1 |

## Controller

- `app/Http/Controllers` (16): `Controller.php`, `DailyNoteExportController.php`, `DocumentSignatureVerificationController.php`, `HealthCheckController.php`, `LetterTypeController.php`, `LetterVerificationController.php`, `MediaDownloadController.php`, `ModernPageController.php`, `ProposalExportController.php`, `ReportExportController.php`, `ReportVerificationController.php`, `ReviewExportController.php`, `RoleSwitcherController.php`, `SintaExportController.php`, `ThemeSwitcherController.php`, `TwoFactorChallengeController.php`
- `app/Http/Controllers/AdminLppm` (1): `ManualBookUploadController.php`
- `app/Http/Controllers/Api` (6): `DailyNoteController.php`, `ModernProposalController.php`, `PublicCommunityServiceController.php`, `PublicResearchController.php`, `PublicStatsController.php`, `VerifikasiLuaranController.php`
- `app/Http/Controllers/Api/Settings` (6): `CaptchaApiController.php`, `FeatureFlagsApiController.php`, `NotificationConfigApiController.php`, `PasswordApiController.php`, `ProfileApiController.php`, `SettingsHubApiController.php`
- `app/Http/Controllers/Auth` (2): `MathCaptchaController.php`, `VerifyEmailController.php`
- `app/Http/Controllers/Settings` (3): `BackupDownloadController.php`, `PdfPreviewController.php`, `ProposalTemplateUploadController.php`

## Komponen Livewire

- `app/Livewire` (1): `Dashboard.php`
- `app/Livewire/Abstracts` (4): `ProposalCreate.php`, `ProposalIndex.php`, `ProposalShow.php`, `ReportIndex.php`
- `app/Livewire/Actions` (7): `ApproveProposalAction.php`, `AssignReviewersAction.php`, `CompleteReviewAction.php`, `DekanApprovalAction.php`, `Logout.php`, `RequestReReviewAction.php`, `SubmitProposalAction.php`
- `app/Livewire/Admin` (1): `EligibilityDashboard.php`
- `app/Livewire/Admin/Archive` (1): `ManageArchives.php`
- `app/Livewire/AdminLppm` (7): `ExportSinta.php`, `NotificationLog.php`, `ReportApproval.php`, `ReviewMonitoring.php`, `ReviewerAssignment.php`, `ReviewerWorkload.php`, `SyncSinta.php`
- `app/Livewire/AdminLppm/Letter` (1): `Dashboard.php`
- `app/Livewire/AdminLppm/ManualBook` (2): `Form.php`, `Index.php`
- `app/Livewire/AdminLppm/Monev` (1): `MonevIndex.php`
- `app/Livewire/Auth` (4): `ForgotPassword.php`, `Login.php`, `ResetPassword.php`, `VerifyEmail.php`
- `app/Livewire/BudgetAmendment` (1): `Manager.php`
- `app/Livewire/CommunityService/DailyNote` (2): `Index.php`, `Show.php`
- `app/Livewire/CommunityService/FinalReport` (2): `Index.php`, `Show.php`
- `app/Livewire/CommunityService/Proposal` (13): `ApprovalButton.php`, `Create.php`, `Edit.php`, `Index.php`, `KepalaLppmFinalDecision.php`, `KepalaLppmInitialApproval.php`, `ReviewerAssignment.php`, `ReviewerForm.php`, `Show.php`, `SubmitButton.php`, `TeamMemberForm.php`, `TeamMemberInvitation.php`, `TeamMemberInvitations.php`
- `app/Livewire/CommunityService/ProposalRevision` (2): `Index.php`, `Show.php`
- `app/Livewire/Concerns` (3): `HasLetterForm.php`, `HasTeamSearch.php`, `HasToast.php`
- `app/Livewire/Dashboard` (6): `AdminDashboard.php`, `DefaultDashboard.php`, `DosenDashboard.php`, `ExecDashboard.php`, `KepalaLppmDashboard.php`, `ReviewerDashboard.php`
- `app/Livewire/Dashboard/Concerns` (1): `HasProcessDetailsModal.php`
- `app/Livewire/Dashboard/Dosen` (3): `LetterDashboard.php`, `LetterManualRequest.php`, `LetterProposalLinkedRequest.php`
- `app/Livewire/Dashboard/KepalaLppm` (1): `LetterApproval.php`
- `app/Livewire/Dashboard/Letters` (1): `LetterList.php`
- `app/Livewire/Dekan` (3): `ApprovalHistory.php`, `ProposalIndex.php`, `ReportIndex.php`
- `app/Livewire/FinalReport` (1): `BaseFinalReportShow.php`
- `app/Livewire/Forms` (3): `ProposalForm.php`, `ReportForm.php`, `TeamMembersForm.php`
- `app/Livewire/Forms/Installer` (4): `AdminAccountForm.php`, `DatabaseConfigForm.php`, `EnvironmentConfigForm.php`, `InstitutionSetupForm.php`
- `app/Livewire/Iku` (2): `IkuDashboard.php`, `IkuVerification.php`
- `app/Livewire/Installer` (1): `InstallerWizard.php`
- `app/Livewire/Kaprodi` (2): `ProposalValidation.php`, `ReportApproval.php`
- `app/Livewire/KepalaLppm` (4): `FinalDecision.php`, `FinancialApproval.php`, `InitialApproval.php`, `ReportApproval.php`
- `app/Livewire/KepalaLppm/Monev` (1): `MonevRecap.php`
- `app/Livewire/Lecturer/PolicyInvolvement` (1): `Index.php`
- `app/Livewire/ManualBook` (1): `Index.php`
- `app/Livewire/Notifications` (2): `NotificationCenter.php`, `NotificationDropdown.php`
- `app/Livewire/Proposals` (1): `ProposalActivityTimeline.php`
- `app/Livewire/Rektor` (1): `MonevDashboard.php`
- `app/Livewire/Reports` (9): `AbstractInstitutionalReport.php`, `CommunityService.php`, `IkuReport.php`, `InstitutionalReportMonitoring.php`, `MonevReport.php`, `OutputReports.php`, `PartnerCollaboration.php`, `Research.php`, `ReviewerReport.php`
- `app/Livewire/Research/DailyNote` (2): `Index.php`, `Show.php`
- `app/Livewire/Research/FinalReport` (2): `Index.php`, `Show.php`
- `app/Livewire/Research/Proposal` (13): `ApprovalButton.php`, `Create.php`, `Edit.php`, `Index.php`, `KepalaLppmFinalDecision.php`, `KepalaLppmInitialApproval.php`, `ReviewerAssignment.php`, `ReviewerForm.php`, `Show.php`, `SubmitButton.php`, `TeamMemberForm.php`, `TeamMemberInvitation.php`, `TeamMemberInvitations.php`
- `app/Livewire/Research/Proposal/Components` (3): `SubstansiUsulan.php`, `TktMeasurement.php`, `WorkflowActions.php`
- `app/Livewire/Research/ProposalRevision` (2): `Index.php`, `Show.php`
- `app/Livewire/Review` (3): `CommunityService.php`, `Research.php`, `ReviewHistory.php`
- `app/Livewire/Reviewer/Monev` (1): `Index.php`
- `app/Livewire/Settings` (22): `Appearance.php`, `AuditLog.php`, `BackupData.php`, `CaptchaSettings.php`, `ContractManager.php`, `DataSync.php`, `DeleteUserForm.php`, `FeatureFlags.php`, `ManualBookSetting.php`, `MasterData.php`, `NotificationConfig.php`, `Password.php`, `PdfExportSettings.php`, `PdfModuleCard.php`, `ProfileForm.php`, `ProposalSchedule.php`, `ProposalTemplate.php`, `RestoreData.php`, `ReviewCriteriaManager.php`, `RolePermissionMatrix.php`, `SettingsIndex.php`, `TwoFactor.php`
- `app/Livewire/Settings/Tabs` (23): `BudgetCapManager.php`, `BudgetComponentManager.php`, `BudgetGroupManager.php`, `CommunityServiceSchemeManager.php`, `FacultyManager.php`, `FacultyRoadmapManager.php`, `FocusAreaManager.php`, `IkuOutputTypeManager.php`, `InstitutionManager.php`, `KeywordManager.php`, `MasterIkuManager.php`, `NationalPriorityManager.php`, `PartnerManager.php`, `QuotaMessageManager.php`, `ResearchSchemeManager.php`, `ScienceClusterManager.php`, `SdgManager.php`, `StrataManager.php`, `StudyProgramManager.php`, `StudyProgramRoadmapManager.php`, `ThemeManager.php`, `TktManager.php`, `TopicManager.php`
- `app/Livewire/Settings/TwoFactor` (1): `RecoveryCodes.php`
- `app/Livewire/Traits` (14): `HasFileUploads.php`, `HasReportTemplates.php`, `ManagesAdditionalOutputs.php`, `ReportAccess.php`, `ReportAuthorization.php`, `ReportData.php`, `ReportFilters.php`, `WithApproval.php`, `WithFilters.php`, `WithInstitutionalApproval.php`, `WithProposalWizard.php`, `WithReportApproval.php`, `WithStepWizard.php`, `WithTeamManagement.php`
- `app/Livewire/Users` (6): `ActivityLogList.php`, `Create.php`, `Edit.php`, `Import.php`, `Index.php`, `Show.php`

## Artisan command

- `app/Console/Commands` (43): `AuditBudgetCaps.php`, `BackfillIdentityScienceCluster.php`, `BenchmarkNotifications.php`, `BugCapture.php`, `ChangelogBuild.php`, `CheckCopyArtifacts.php`, `CheckModernPages.php`, `CheckMySqlColumnOrders.php`, `CheckOverdueReviews.php`, `CheckRegressions.php`, `CheckSchemaDrift.php`, `CheckTheme.php`, `CleanupDummyData.php`, `CleanupOrphanMedia.php`, `ClearRateLimiters.php`, `CodebaseMap.php`, `DbSnapshot.php`, `DigestWeekly.php`, `FixDocSubstanceFiles.php`, `FixProposalSignatures.php`, `FixProposalSubstanceFile.php`, `GenerateMySqlColumnOrders.php`, `HistoryBrief.php`, `HistorySync.php`, `ImportSintaAuthor.php`, `ImportUsers.php`, `InstallCommand.php`, `MigrateMediaPaths.php`, `PatchSoftwareTkt.php`, `PruneLogs.php`, `PruneObservability.php`, `RecordDevEvent.php`, `RegressionHealth.php`, `RepairLetterData.php`, `ResetPdfSettings.php`, `RestoreBackupCommand.php`, `RunInstallationCommand.php`, `SendDailySummaries.php`, `SendReviewReminders.php`, `SendWeeklySummaries.php`, `SyncEnums.php`, `SyncProductionData.php`, `ValidateMySqlColumnOrders.php`
- `app/Console/Commands/Traits` (1): `ParsesMigrationColumns.php`

## Model

- `app/Models` (67): `ActivityLog.php`, `ActivitySchedule.php`, `AdditionalOutput.php`, `BudgetAmendment.php`, `BudgetAmendmentItem.php`, `BudgetCap.php`, `BudgetComponent.php`, `BudgetGroup.php`, `BudgetItem.php`, `CommunityService.php`, `CommunityServiceScheme.php`, `DailyNote.php`, `DevEvent.php`, `DocumentSignature.php`, `ErrorSignature.php`, `Faculty.php`, `FacultyRoadmap.php`, `FocusArea.php`, `Identity.php`, `IkuOutputType.php`, `Institution.php`, `InstitutionalReport.php`, `KaprodiApproval.php`, `Keyword.php`, `Letter.php`, `LetterCategory.php`, `LetterLog.php`, `LetterType.php`, `MacroResearchGroup.php`, `MandatoryOutput.php`, `ManualBook.php`, `MasterIku.php`, `MonevReview.php`, `NationalPriority.php`, `NotificationLog.php`, `NotificationSetting.php`, `Partner.php`, `Permission.php`, `PolicyInvolvement.php`, `ProgressReport.php`, `Proposal.php`, `ProposalActivity.php`, `ProposalMonev.php`, `ProposalOutput.php`, `ProposalReviewer.php`, `ProposalStatusLog.php`, `QuotaMessage.php`, `Research.php`, `ResearchScheme.php`, `ResearchStage.php`, `ReviewCriteria.php`, `ReviewLog.php`, `ReviewScore.php`, `ReviewerProfile.php`, `Role.php`, `ScienceCluster.php`, `Sdg.php`, `Setting.php`, `SintaScoreSubmission.php`, `Strata.php`, `StudyProgram.php`, `StudyProgramRoadmap.php`, `Theme.php`, `TktIndicator.php`, `TktLevel.php`, `Topic.php`, `User.php`

## Test (per folder)

| Folder | Jumlah file |
|---|---|
| `.` | 2 |
| `Feature` | 52 |
| `Feature/Admin` | 1 |
| `Feature/AdminLppm` | 1 |
| `Feature/Api` | 7 |
| `Feature/Api/Settings` | 7 |
| `Feature/Auth` | 9 |
| `Feature/Console` | 13 |
| `Feature/Dekan` | 1 |
| `Feature/Iku` | 2 |
| `Feature/Installer` | 2 |
| `Feature/Kaprodi` | 1 |
| `Feature/KepalaLppm` | 4 |
| `Feature/Livewire/Research` | 1 |
| `Feature/Livewire/Research/Proposal/Components` | 1 |
| `Feature/Menu` | 1 |
| `Feature/Observability` | 1 |
| `Feature/Regression` | 3 |
| `Feature/Reports` | 2 |
| `Feature/Security` | 7 |
| `Feature/Services` | 1 |
| `Feature/Settings` | 14 |
| `Feature/Theme` | 9 |
| `Support` | 1 |
| `Unit` | 3 |
| `Unit/Commands` | 1 |
| `Unit/Migrations` | 1 |
| `Unit/Services` | 1 |
| `e2e` | 1 |

## Kelas bug (registry regresi)

- `BC-01-theme-boundary.md`
- `BC-02-active-role.md`
- `BC-03-ui-api-drift.md`
- `BC-04-state-machine-ui.md`
- `BC-05-mass-assignment.md`
- `BC-06-idor-bola.md`
- `BC-07-stale-build.md`
- `BC-08-workflow-dead-end.md`
- `BC-09-ai-copy-artifact.md`
- `BC-10-pagu-anggaran-per-skema-tidak-bisa-dieditdisimpan-modal-tamp.md`

## Dokumen acuan

- `AGENTS.md` — workflow & Definition of Done
- `docs/plans/master-plan.md` — urutan eksekusi & mitigasi
- `docs/plans/gap-analysis-modern-classic.md` — gap tema
- `docs/regressions/README.md` — registry kelas bug
