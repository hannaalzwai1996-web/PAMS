<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per docs/database/0001-database-design.md §3.5. Backs BR-5 / FR-PROG-09
 * ("a Program Coordinator may only view/edit programs they're assigned
 * to") — needed now because ProgramObjectivePolicy enforces exactly that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->ulid('program_id');
            $table->ulid('user_id');
            $table->timestamp('assigned_at')->useCurrent();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['program_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_coordinator_assignments');
    }
};
