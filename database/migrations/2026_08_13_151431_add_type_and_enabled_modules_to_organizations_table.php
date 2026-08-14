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
        if (! Schema::hasColumn('organizations', 'type')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('type')->default('mixed');
            });
        }

        if (! Schema::hasColumn('organizations', 'enabled_modules')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->json('enabled_modules')->default('["club","stable"]');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'enabled_modules')) {
                $table->dropColumn('enabled_modules');
            }

            if (Schema::hasColumn('organizations', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
