<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            if (! Schema::hasColumn('letter_types', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('letter_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_uploadable');
            }
            if (! Schema::hasColumn('letter_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            if (Schema::hasColumn('letter_types', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $table->dropColumn(['description', 'is_active']);
        });
    }
};
