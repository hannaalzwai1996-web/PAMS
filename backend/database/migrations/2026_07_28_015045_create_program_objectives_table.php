<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per docs/database/0001-database-design.md §3.7 ("Objectives" / PEOs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_objectives', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('program_id');
            $table->string('code', 20);
            $table->text('statement');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->unique(['program_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_objectives');
    }
};
