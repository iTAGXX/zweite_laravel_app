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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUuid('recurrence_id')->nullable()->constrained('task_recurrences')->nullOnDelete();
            $table->string('occurrence_key')->nullable();

            $table->unique(['organization_id', 'occurrence_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'occurrence_key']);
            $table->dropConstrainedForeignId('recurrence_id');
            $table->dropColumn('occurrence_key');
        });
    }
};
