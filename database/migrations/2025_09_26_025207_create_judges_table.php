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
        Schema::create('judges', function (Blueprint $table) {
            $table->id();

            // Link to user (judge account)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Link to annual event
            $table->foreignId('event_id')
                ->constrained('pageant_events')
                ->cascadeOnDelete();

            // Optional metadata
            $table->string('category_assignment')->nullable(); // e.g., 'Swimwear', 'Talent', etc.
            $table->unsignedInteger('judge_number')->nullable(); // if needed for seat/order
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Ensure same user can’t be a judge twice for same event
            $table->unique(['user_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
