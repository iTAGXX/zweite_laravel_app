<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addColumnIfNotExists('type', function (Blueprint $table) {
            $table->string('type')->default('mixed');
        });

        $this->addColumnIfNotExists('enabled_modules', function (Blueprint $table) {
            $table->json('enabled_modules')->default('["club","stable"]');
        });
    }

    /**
     * Add a column to organizations only when it does not already exist.
     * Uses try/catch in addition to hasColumn because MySQL DDL is non-transactional:
     * a partially-applied ALTER is visible in the DB but absent from the migrations table,
     * causing a duplicate-column error (SQLSTATE 42S21 / MySQL 1060) on the next deploy.
     */
    private function addColumnIfNotExists(string $column, Closure $definition): void
    {
        if (Schema::hasColumn('organizations', $column)) {
            return;
        }

        try {
            Schema::table('organizations', $definition);
        } catch (QueryException $e) {
            // 42S21 = duplicate column (MySQL/MariaDB error 1060)
            if ($e->getCode() !== '42S21') {
                throw $e;
            }
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
