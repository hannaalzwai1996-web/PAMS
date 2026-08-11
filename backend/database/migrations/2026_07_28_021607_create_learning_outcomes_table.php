<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per docs/database/0001-database-design.md §3.8 (PLOs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('program_id');
            $table->string('code', 20);
            $table->text('statement');
            $table->string('category', 1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->unique(['program_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_outcomes');
    }
};
