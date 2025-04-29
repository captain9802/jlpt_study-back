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
        Schema::create('favorite_sentences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('favorite_sentence_lists')->onDelete('cascade');
            $table->text('text');
            $table->text('translation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_sentences');
    }
};
