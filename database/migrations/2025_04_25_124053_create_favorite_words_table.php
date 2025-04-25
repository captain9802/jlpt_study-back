<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('favorite_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('favorite_word_lists')->onDelete('cascade');
            $table->string('text');
            $table->string('reading');
            $table->string('meaning');
            $table->string('onyomi')->nullable();
            $table->string('kunyomi')->nullable();
            $table->json('examples')->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_words');
    }
};
