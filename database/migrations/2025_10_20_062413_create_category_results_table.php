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
        Schema::create('category_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contestant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('pageant_events')->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('average_score', 8, 5)->default(0);
            $table->decimal('category_total', 8, 5)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();

            $table->unique(['contestant_id', 'category_id', 'event_id'], 'unique_contestant_category_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_results');
    }
};
