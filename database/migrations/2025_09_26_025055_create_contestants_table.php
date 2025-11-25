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
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('pageant_events')->cascadeOnDelete();
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->string('number')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'gender']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contestants');
    }
};
