<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per docs/architecture/0002-po-plo-matrix-engine.md §4. Default 'manual'
 * because every row that could already exist was created via the explicit
 * per-outcome sync endpoint, i.e. a manual action — not a guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objective_outcome_matrix', function (Blueprint $table) {
            $table->string('source', 10)->default('manual')->after('correlation_level');
        });
    }

    public function down(): void
    {
        Schema::table('objective_outcome_matrix', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
