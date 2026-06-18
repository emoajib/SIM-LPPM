<?php

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
        Schema::table('mandatory_outputs', function (Blueprint $table) {
            if (! Schema::hasColumn('mandatory_outputs', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('rank');
            }
            if (! Schema::hasColumn('mandatory_outputs', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_verified');
            }
            if (! Schema::hasColumn('mandatory_outputs', 'verified_by')) {
                // Use uuid type for PostgreSQL compatibility with users.id
                $table->uuid('verified_by')->nullable()->after('verified_at');
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandatory_outputs', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['is_verified', 'verified_at', 'verified_by']);
        });
    }
};
