<?php

use App\Enums\AuthorStatus;
use App\Enums\OutputStatusType;
use Database\Helpers\MigrationHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mandatory_outputs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('progress_report_id')->comment('Progress Report')->constrained()->onDelete('cascade');
            $table->foreignId('proposal_output_id')->comment('Link to planned output')->constrained('proposal_outputs')->onDelete('cascade');

            // Status & Author Information
            $table->string('status_type', 50)->comment('Publication status');
            $table->string('author_status', 50)->comment('Author role');

            // Journal Information
            $table->string('journal_title')->comment('Nama jurnal');
            $table->string('issn', 20)->nullable()->comment('ISSN number');
            $table->string('eissn', 20)->nullable()->comment('E-ISSN number');
            $table->string('indexing_body')->nullable()->comment('Scopus/WoS/Sinta');
            $table->string('journal_url', 500)->nullable()->comment('Journal website URL');

            // Article Information
            $table->string('article_title')->comment('Judul artikel');
            $table->year('publication_year')->comment('Tahun publikasi');
            $table->string('volume', 50)->nullable()->comment('Volume');
            $table->string('issue_number', 50)->nullable()->comment('Issue/nomor');
            $table->integer('page_start')->nullable()->comment('Halaman awal');
            $table->integer('page_end')->nullable()->comment('Halaman akhir');
            $table->string('article_url', 500)->nullable()->comment('Article URL');
            $table->string('doi', 255)->nullable()->comment('DOI');
            $table->string('document_file')->nullable()->comment('Uploaded PDF file');

            $table->timestamps();

            $table->index('progress_report_id');
            $table->index('proposal_output_id');
        });

        // Add CHECK constraints for enum columns
        MigrationHelpers::addCheckConstraintToTable(
            'mandatory_outputs',
            'status_type',
            OutputStatusType::values(),
            MigrationHelpers::generateConstraintName('mandatory_outputs', 'status_type')
        );

        MigrationHelpers::addCheckConstraintToTable(
            'mandatory_outputs',
            'author_status',
            AuthorStatus::values(),
            MigrationHelpers::generateConstraintName('mandatory_outputs', 'author_status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandatory_outputs');
    }
};
