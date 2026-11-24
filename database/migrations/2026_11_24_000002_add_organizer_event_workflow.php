<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('organizer_approved')->default(false);
        });

        Schema::table('open_play_sessions', function (Blueprint $table) {
            $table->foreignId('organizer_id')->nullable()->after('manager_id')->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->unsignedSmallInteger('player_limit')->nullable();
            $table->text('rules')->nullable();
            $table->boolean('scheduling_paused')->default(false);
            $table->index(['organizer_id', 'play_date']);
        });

        Schema::create('open_play_session_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['open_play_session_id', 'manager_id']);
        });

        Schema::table('session_players', function (Blueprint $table) {
            $table->boolean('fee_paid')->default(false);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('court_id')->nullable()->change();
            $table->timestamp('started_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('matches')->where('status', 'queued')->delete();

        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('court_id')->nullable(false)->change();
            $table->timestamp('started_at')->nullable(false)->change();
        });

        Schema::table('session_players', function (Blueprint $table) {
            $table->dropColumn('fee_paid');
        });

        Schema::dropIfExists('open_play_session_managers');

        Schema::table('open_play_sessions', function (Blueprint $table) {
            $table->dropIndex(['organizer_id', 'play_date']);
            $table->dropConstrainedForeignId('organizer_id');
            $table->dropColumn(['starts_at', 'player_limit', 'rules', 'scheduling_paused']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('organizer_approved');
        });
    }
};
