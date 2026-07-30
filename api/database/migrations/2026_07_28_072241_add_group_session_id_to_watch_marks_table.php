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
        Schema::table('watch_marks', function (Blueprint $table) {
            $table->foreignId('group_session_id')->nullable()->after('episode_id')
                ->constrained('group_sessions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_marks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_session_id');
        });
    }
};
