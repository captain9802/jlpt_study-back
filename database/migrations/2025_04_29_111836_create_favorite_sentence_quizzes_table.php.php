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
        Schema::create('favorite_sentence_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentence_id')->constrained('favorite_sentences')->onDelete('cascade');
            $table->text('question');
            $table->text('question_ko');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_sentence_quizzes');
    }
};
