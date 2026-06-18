<?php

use App\Enums\ProposalStatus;
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
        Schema::create('proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->comment('Judul Proposal');
            $table->foreignUuid('submitter_id')->comment('Pengaju')->constrained('users')->onDelete('cascade');
            $table->uuid('detailable_id')->nullable();
            $table->string('detailable_type')->nullable();
            $table->foreignId('research_scheme_id')->nullable()->comment('Skema Penelitian')->constrained('research_schemes')->onDelete('set null');
            $table->foreignId('focus_area_id')->nullable()->comment('Bidang Fokus')->constrained('focus_areas')->onDelete('set null');
            $table->foreignId('theme_id')->nullable()->comment('Tema')->constrained('themes')->onDelete('set null');
            $table->foreignId('topic_id')->nullable()->comment('Topik')->constrained('topics')->onDelete('set null');
            $table->foreignId('national_priority_id')->nullable()->comment('Prioritas Riset Nasional')->constrained('national_priorities')->onDelete('set null');
            $table->foreignId('cluster_level1_id')->nullable()->comment('Rumpun Ilmu Level 1')->constrained('science_clusters')->onDelete('set null');
            $table->foreignId('cluster_level2_id')->nullable()->comment('Rumpun Ilmu Level 2')->constrained('science_clusters')->onDelete('set null');
            $table->foreignId('cluster_level3_id')->nullable()->comment('Rumpun Ilmu Level 3')->constrained('science_clusters')->onDelete('set null');
            $table->decimal('sbk_value', 15, 2)->nullable()->comment('Nilai SBK');
            $table->integer('duration_in_years')->default(1)->comment('Lama Kegiatan (tahun)');
            $table->text('summary')->nullable()->comment('Ringkasan');
            $table->string('status', 50)->default(ProposalStatus::DRAFT->value)->comment('Status Proposal');
            $table->timestamps();
        });

        // Add CHECK constraint for status enum
        MigrationHelpers::addCheckConstraint(
            'proposals',
            'status',
            ProposalStatus::values(),
            MigrationHelpers::generateConstraintName('proposals', 'status')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
