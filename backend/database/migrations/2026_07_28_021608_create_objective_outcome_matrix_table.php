<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "PO-PLO Matrix" — per docs/database/0001-database-design.md §3.9.
 * correlation_level: 1 = Low, 2 = Medium, 3 = High.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_outcome_matrix', function (Blueprint $table) {
            $table->id();
            $table->ulid('program_objective_id');
            $table->ulid('learning_outcome_id');
            $table->unsignedTinyInteger('correlation_level');
            $table->timestamps();

            $table->foreign('program_objective_id')->references('id')->on('program_objectives')->cascadeOnDelete();
            $table->foreign('learning_outcome_id')->references('id')->on('learning_outcomes')->cascadeOnDelete();
            $table->unique(['program_objective_id', 'learning_outcome_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objective_outcome_matrix');
    }
};
