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
        Schema::table('users', function (Blueprint $table) {
            // §3.1: a Compte can belong to several Foyers (via household_memberships) —
            // current_household_id is just "which one is active in this session's UI",
            // not an ownership link. Personal data (progression, watchlist...) never
            // references a household at all.
            $table->foreignId('current_household_id')->nullable()->after('id')
                ->constrained('households')->nullOnDelete();
            $table->string('initial', 2)->default('?')->after('name');
            $table->string('color', 7)->default('#F0B091')->after('initial');
            $table->enum('visibility_global', ['privee', 'foyers', 'groupes'])->default('privee')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_household_id');
            $table->dropColumn(['initial', 'color', 'visibility_global']);
        });
    }
};
