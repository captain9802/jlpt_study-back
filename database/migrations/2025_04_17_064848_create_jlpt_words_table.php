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
        Schema::create('jlpt_words', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->string('kana')->nullable();
            $table->text('meaning_ko');
            $table->json('levels'); // N5, N4 배열 저장
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jlpt_words');
    }
};
