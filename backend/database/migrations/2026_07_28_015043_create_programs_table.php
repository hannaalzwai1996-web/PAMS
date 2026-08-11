<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per docs/database/0001-database-design.md §3.4. This is a minimal,
 * dependency-only definition — just enough for Program Objectives to have
 * a real parent to belong to. The full Program module (submit/approve
 * workflow, versioning) is separate, future work; `status` and
 * `current_version_no` exist now only because ProgramObjectiveService
 * already needs to know whether a program is still in `draft`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 200);
            $table->string('level', 20);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('duration_years');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('current_version_no')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
