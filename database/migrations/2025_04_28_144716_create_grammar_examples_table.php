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
        Schema::create('grammar_examples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grammar_id');
            $table->text('ja');
            $table->text('ko');
            $table->timestamps();

            $table->foreign('grammar_id')->references('id')->on('favorite_grammars')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grammar_examples');
    }
};
