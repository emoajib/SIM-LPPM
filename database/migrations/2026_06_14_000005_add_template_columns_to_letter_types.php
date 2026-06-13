<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            $table->string('template_file_path')->nullable()->after('template_view');
            $table->string('template_file_original_name')->nullable()->after('template_file_path');
            $table->integer('template_file_size')->nullable()->after('template_file_original_name');
            $table->timestamp('template_uploaded_at')->nullable()->after('template_file_size');
            $table->foreignId('template_uploaded_by')->nullable()->after('template_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            $table->dropColumn([
                'template_file_path',
                'template_file_original_name',
                'template_file_size',
                'template_uploaded_at',
                'template_uploaded_by',
            ]);
        });
    }
};
