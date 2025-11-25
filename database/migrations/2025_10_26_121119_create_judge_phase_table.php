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
        Schema::create('judge_phase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->unique(['judge_id', 'phase_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judge_phase');
    }
};
