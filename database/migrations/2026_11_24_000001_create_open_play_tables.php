<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('venue');
            $table->date('play_date');
            $table->string('skill_level');
            $table->string('format');
            $table->string('share_token')->unique();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->index(['manager_id', 'play_date']);
        });

        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('session_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['open_play_session_id', 'user_id']);
            $table->index(['open_play_session_id', 'checked_in_at']);
        });

        Schema::create('saved_player_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('saved_player_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_player_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['saved_player_group_id', 'user_id']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('playing');
            $table->unsignedSmallInteger('team_one_score')->nullable();
            $table->unsignedSmallInteger('team_two_score')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['open_play_session_id', 'status']);
        });

        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('team');
            $table->unique(['match_id', 'session_player_id']);
            $table->index(['session_player_id', 'team']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_participants');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('saved_player_group_members');
        Schema::dropIfExists('saved_player_groups');
        Schema::dropIfExists('session_players');
        Schema::dropIfExists('courts');
        Schema::dropIfExists('open_play_sessions');
    }
};
