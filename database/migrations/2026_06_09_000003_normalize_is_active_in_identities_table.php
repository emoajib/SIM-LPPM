<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert all string values to boolean
        // Handle 'Aktif' (Indonesian) and 'Active' (English)
        DB::table('identities')
            ->where('is_active', 'Aktif')
            ->update(['is_active' => '1']);

        DB::table('identities')
            ->where('is_active', 'Active')
            ->update(['is_active' => '1']);

        // Set any remaining non-true values to false
        DB::table('identities')
            ->where('is_active', '!=', '1')
            ->update(['is_active' => '0']);

        // Change column type from string to boolean
        // PostgreSQL requires explicit USING clause for type casting
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE identities ALTER COLUMN is_active TYPE boolean USING (is_active::int::boolean)');
            DB::statement('ALTER TABLE identities ALTER COLUMN is_active SET DEFAULT true');
        } else {
            Schema::table('identities', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change column type back to string
        Schema::table('identities', function (Blueprint $table) {
            $table->string('is_active')->default('Aktif')->change();
        });

        // Convert boolean values back to string
        DB::table('identities')
            ->where('is_active', true)
            ->update(['is_active' => 'Aktif']);

        DB::table('identities')
            ->where('is_active', false)
            ->update(['is_active' => 'Non Aktif']);
    }
};
