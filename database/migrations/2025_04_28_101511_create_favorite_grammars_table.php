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
        Schema::create('favorite_grammars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id');
            $table->text('grammar');
            $table->text('meaning');
            $table->timestamps();

            $table->foreign('list_id')->references('id')->on('favorite_grammar_lists')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_grammars');
    }
};
