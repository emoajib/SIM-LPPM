<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('total_price');
            $table->unsignedInteger('version')->default(1)->after('is_active');
            $table->uuid('budget_amendment_id')->nullable()->after('version');
        });

        Schema::create('budget_amendments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('proposal_id');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->text('decision_notes')->nullable();
            $table->uuid('requested_by')->nullable();
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('proposal_id')->references('id')->on('proposals')->cascadeOnDelete();
            $table->unique(['proposal_id', 'version']);
        });

        Schema::create('budget_amendment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('budget_amendment_id');
            $table->unsignedBigInteger('budget_group_id')->nullable();
            $table->unsignedBigInteger('budget_component_id')->nullable();
            $table->integer('year')->nullable();
            $table->string('group')->nullable();
            $table->string('component')->nullable();
            $table->string('item_description')->nullable();
            $table->decimal('volume', 15, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('budget_amendment_id')->references('id')->on('budget_amendments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_amendment_items');
        Schema::dropIfExists('budget_amendments');
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'version', 'budget_amendment_id']);
        });
    }
};
