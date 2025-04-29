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
        Schema::create('favorite_sentence_quiz_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('favorite_sentence_quizzes')->onDelete('cascade');
            $table->string('text');
            $table->string('meaning')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_sentence_quiz_choices');
    }
};
