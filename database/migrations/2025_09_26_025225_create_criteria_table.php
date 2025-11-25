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
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('pageant_events')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('criteria')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('percentage', 5, 2);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
