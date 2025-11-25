<?php

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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('pageant_events')
                ->cascadeOnDelete();

            $table->foreignId('judge_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contestant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('criterion_id')
                ->constrained('criteria')
                ->cascadeOnDelete();

            $table->decimal('score', 8, 5)->default(0); // flexible decimal precision
            $table->decimal('weighted_score', 8, 5)->default(0);

            $table->timestamps();

            $table->unique(['event_id','judge_id','contestant_id','criterion_id'], 'unique_score_per_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
