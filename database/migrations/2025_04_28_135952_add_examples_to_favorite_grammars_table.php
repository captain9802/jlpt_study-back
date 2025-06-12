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
        Schema::table('favorite_grammars', function (Blueprint $table) {
            if (Schema::hasColumn('favorite_grammars', 'example_1')) {
                $table->dropColumn('example_1');
            }
            if (Schema::hasColumn('favorite_grammars', 'example_2')) {
                $table->dropColumn('example_2');
            }
            if (Schema::hasColumn('favorite_grammars', 'example_3')) {
                $table->dropColumn('example_3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favorite_grammars', function (Blueprint $table) {
            $table->string('example_1')->nullable();
            $table->string('example_2')->nullable();
            $table->string('example_3')->nullable();
        });
    }
};
