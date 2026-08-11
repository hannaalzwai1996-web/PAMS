<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The existing UNIQUE(program_id, user_id) index only serves lookups that
 * filter by program_id first (leftmost-prefix rule) — a query filtering
 * by user_id alone (e.g. a future "programs I coordinate" listing, or
 * Program::hasCoordinator() called the other way round) would otherwise
 * force a full table scan. Found during the pre-deployment quality
 * review — docs/reviews/0001-quality-review.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_coordinator_assignments', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('program_coordinator_assignments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
