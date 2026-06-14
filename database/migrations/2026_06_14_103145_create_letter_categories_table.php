<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Insert default categories
        $defaults = [
            ['name' => 'Persiapan', 'slug' => 'persiapan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Etik', 'slug' => 'etik', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pelaksanaan', 'slug' => 'pelaksanaan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pelaporan', 'slug' => 'pelaporan', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('letter_categories')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_categories');
    }
};
