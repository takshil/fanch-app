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
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tvmaze_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('platform')->nullable();
            $table->string('genre')->nullable();
            $table->string('status')->nullable();
            $table->unsignedSmallInteger('seasons_count')->default(1);
            $table->string('color', 7)->default('#A9C3E3');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
