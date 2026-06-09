<?php

namespace App\Console\Commands;

use App\Models\Identity;
use App\Models\Institution;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
// We might use this if we want to use the facade, but we can use PHPOffice directly to avoid facade issues in commands sometimes
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportSintaAuthor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:sinta-author {file? : Path to the Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import authors from SINTA Excel export';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');

        if (! $file) {
            // Try to find the file in the root directory
            $files = glob(base_path('export_author_*.xlsx'));
            if (empty($files)) {
                $this->error('No export_author_*.xlsx file found in root directory.');

                return 1; // Failure
            }
            $file = $files[0];
        }

        $this->info("Reading file: $file");

        try {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();

            // Headers are at row 5 (index 5 if 1-based)
            // Data starts at row 6

            $startRow = 6;
            $highestRow = $worksheet->getHighestRow();

            $this->info("Found {$highestRow} rows. processing...");

            $institution = Institution::first(); // Default institution
            if (! $institution) {
                $this->warn('No institution found in database. Please seed institutions first.');
            }

            $count = 0;
            $updated = 0;
            $created = 0;

            for ($row = $startRow; $row <= $highestRow; $row++) {
                // Read columns based on our inspection
                // A=0, B=1, ...
                // NO (0)
                // SINTAID (1)
                // NIDN (2)
                // NAMA (3)
                // AFILIASI (4)
                // PRODI (5)

                $sintaId = $worksheet->getCell("B$row")->getValue();
                $nidn = $worksheet->getCell("C$row")->getValue();
                $name = $worksheet->getCell("D$row")->getValue();
                $prodiName = $worksheet->getCell("F$row")->getValue();

                if (empty($nidn) || empty($name)) {
                    continue; // Skip empty rows
                }

                // Sanitize NIDN/NIM
                $nidn = trim((string) $nidn);

                // --- Find Study Program ---
                $studyProgramId = null;
                if ($prodiName) {
                    $sp = StudyProgram::where('name', 'like', '%'.$prodiName.'%')->first();
                    if ($sp) {
                        $studyProgramId = $sp->id;
                    }
                }

                $sintaScoreV2Overall = $worksheet->getCell("K$row")->getValue();
                $sintaScoreV23Yr = $worksheet->getCell("L$row")->getValue();
                $sintaScoreV3Overall = $worksheet->getCell("M$row")->getValue();
                $sintaScoreV33Yr = $worksheet->getCell("N$row")->getValue();
                $affilScoreV3Overall = $worksheet->getCell("O$row")->getValue();
                $affilScoreV33Yr = $worksheet->getCell("P$row")->getValue();

                $scopusDocs = $worksheet->getCell("Q$row")->getValue();
                $scopusCitations = $worksheet->getCell("R$row")->getValue();
                $scopusCitedDocs = $worksheet->getCell("S$row")->getValue();
                $scopusHIndex = $worksheet->getCell("T$row")->getValue();
                $scopusGIndex = $worksheet->getCell("U$row")->getValue();
                $scopusI10Index = $worksheet->getCell("V$row")->getValue();

                $gsDocs = $worksheet->getCell("W$row")->getValue();
                $gsCitations = $worksheet->getCell("X$row")->getValue();
                $gsCitedDocs = $worksheet->getCell("Y$row")->getValue();
                $gsHIndex = $worksheet->getCell("Z$row")->getValue();
                $gsGIndex = $worksheet->getCell("AA$row")->getValue();
                $gsI10Index = $worksheet->getCell("AB$row")->getValue();

                $wosDocs = $worksheet->getCell("AC$row")->getValue();
                $wosCitations = $worksheet->getCell("AD$row")->getValue();
                $wosCitedDocs = $worksheet->getCell("AE$row")->getValue();
                $wosHIndex = $worksheet->getCell("AF$row")->getValue();
                $wosGIndex = $worksheet->getCell("AG$row")->getValue();
                $wosI10Index = $worksheet->getCell("AH$row")->getValue();

                $garudaDocs = $worksheet->getCell("AI$row")->getValue();
                $garudaCitations = $worksheet->getCell("AJ$row")->getValue();
                $garudaCitedDocs = $worksheet->getCell("AK$row")->getValue();

                $statusAktif = $worksheet->getCell("AL$row")->getValue();

                $lastEducation = $worksheet->getCell("G$row")->getValue();
                $functionalPos = $worksheet->getCell("H$row")->getValue();
                $titlePrefix = $worksheet->getCell("I$row")->getValue();
                $titleSuffix = $worksheet->getCell("J$row")->getValue();

                $sintaMetrics = [
                    'last_education' => $lastEducation,
                    'functional_position' => $functionalPos,
                    'title_prefix' => $titlePrefix,
                    'title_suffix' => $titleSuffix,
                    'sinta_score_v2_overall' => (float) $sintaScoreV2Overall,
                    'sinta_score_v2_3yr' => (float) $sintaScoreV23Yr,
                    'sinta_score_v3_overall' => (float) $sintaScoreV3Overall,
                    'sinta_score_v3_3yr' => (float) $sintaScoreV33Yr,
                    'affil_score_v3_overall' => (float) $affilScoreV3Overall,
                    'affil_score_v3_3yr' => (float) $affilScoreV33Yr,
                    'scopus_documents' => (int) $scopusDocs,
                    'scopus_citations' => (int) $scopusCitations,
                    'scopus_cited_documents' => (int) $scopusCitedDocs,
                    'scopus_h_index' => $scopusHIndex > 0 ? (int) $scopusHIndex : null,
                    'scopus_g_index' => (int) $scopusGIndex,
                    'scopus_i10_index' => (int) $scopusI10Index,
                    'gs_documents' => (int) $gsDocs,
                    'gs_citations' => (int) $gsCitations,
                    'gs_cited_documents' => (int) $gsCitedDocs,
                    'gs_h_index' => $gsHIndex > 0 ? (int) $gsHIndex : null,
                    'gs_g_index' => (int) $gsGIndex,
                    'gs_i10_index' => (int) $gsI10Index,
                    'wos_documents' => (int) $wosDocs,
                    'wos_citations' => (int) $wosCitations,
                    'wos_cited_documents' => (int) $wosCitedDocs,
                    'wos_h_index' => $wosHIndex > 0 ? (int) $wosHIndex : null,
                    'wos_g_index' => (int) $wosGIndex,
                    'wos_i10_index' => (int) $wosI10Index,
                    'garuda_documents' => (int) $garudaDocs,
                    'garuda_citations' => (int) $garudaCitations,
                    'garuda_cited_documents' => (int) $garudaCitedDocs,
                    'is_active' => ($statusAktif ?? 'Aktif') === 'Aktif',
                ];

                $this->line("Processing: $name ($nidn)");

                // Check if Identity exists
                $identity = Identity::where('identity_id', $nidn)->first();

                if ($identity) {
                    // Update existing
                    $identity->sinta_id = $sintaId;
                    if ($studyProgramId && ! $identity->study_program_id) {
                        $identity->study_program_id = $studyProgramId;
                    }

                    // Update Metrics
                    $identity->update($sintaMetrics);

                    $updated++;
                } else {
                    // Create User and Identity
                    // Generate Email
                    $email = $nidn.'@lecturer.itsnu.ac.id'; // Default scheme

                    // Check if User exists by email
                    $user = User::where('email', $email)->first();

                    if (! $user) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make('password'), // Default password
                            'email_verified_at' => now(),
                        ]);
                        $user->assignRole('dosen');
                        $created++;
                    }

                    // Create Identity
                    Identity::create(array_merge([
                        'user_id' => $user->id,
                        'identity_id' => $nidn,
                        'sinta_id' => $sintaId,
                        'type' => 'dosen',
                        'institution_id' => $institution?->id,
                        'study_program_id' => $studyProgramId,
                        'address' => 'ITSNU Pekalongan', // Default
                    ], $sintaMetrics));
                }

                $count++;
            }

            $this->info("Import completed: $count processed, $created created, $updated updated.");

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }

        return self::SUCCESS; // Success
    }
}
