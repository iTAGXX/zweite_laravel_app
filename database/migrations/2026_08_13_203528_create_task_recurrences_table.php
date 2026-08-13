<?php

declare(strict_types=1);

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
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('assignee_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('priority')->default('medium');
            $table->string('frequency');
            $table->unsignedTinyInteger('day_of_month');
            $table->unsignedTinyInteger('month')->nullable();
            $table->date('next_occurrence_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();

            $table->index(['paused_at', 'next_occurrence_on']);
            $table->index(['organization_id', 'next_occurrence_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_recurrences');
    }
};
