<?php

use App\Enums\AdditionalOutputStatusType;
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
        Schema::create('additional_outputs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('progress_report_id')->comment('Progress Report')->constrained()->onDelete('cascade');
            $table->foreignId('proposal_output_id')->comment('Link to planned output')->constrained('proposal_outputs')->onDelete('cascade');

            // Status & Book Information
            $table->string('status', 50)->default('draft')->comment('Status buku');
            $table->string('book_title')->comment('Judul buku');
            $table->string('publisher_name')->comment('Nama penerbit');
            $table->string('isbn', 30)->nullable()->comment('ISBN');
            $table->year('publication_year')->nullable()->comment('Tahun terbit');
            $table->integer('total_pages')->nullable()->comment('Jumlah halaman');
            $table->string('publisher_url', 500)->nullable()->comment('URL penerbit');
            $table->string('book_url', 500)->nullable()->comment('URL buku');

            // Documents
            $table->string('document_file')->nullable()->comment('File buku/draft');
            $table->string('publication_certificate')->nullable()->comment('Surat keterangan terbit');

            $table->timestamps();

            $table->index('progress_report_id');
            $table->index('proposal_output_id');
        });

        // Add CHECK constraint for enum column (using expanded enum values)
        MigrationHelpers::addCheckConstraintToTable(
            'additional_outputs',
            'status',
            AdditionalOutputStatusType::values(),
            MigrationHelpers::generateConstraintName('additional_outputs', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_outputs');
    }
};
