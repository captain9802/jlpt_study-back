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
        Schema::create('grammar_choices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('text');
            $table->string('meaning');
            $table->boolean('is_correct')->default(false);
            $table->text('explanation');
            $table->timestamps();

            $table->foreign('quiz_id')->references('id')->on('grammar_quizzes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_choices');
    }
};
