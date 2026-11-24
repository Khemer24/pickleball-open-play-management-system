<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerAccessAndPlayersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_organizers_before_event_creation_is_allowed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => false]);

        $this->actingAs($organizer)->get(route('events.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.managers'))->assertOk()->assertSee($organizer->name)->assertSee('organizer');
        $this->post(route('admin.managers.approve', $organizer))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $organizer->id, 'organizer_approved' => true]);
        $organizer->refresh();
        $this->actingAs($organizer)->get(route('events.create'))->assertOk();
    }

    public function test_players_played_with_shows_only_the_signed_in_users_completed_matches(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => true]);
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $teammate = User::factory()->create(['name' => 'Teammate Sample']);
        $opponent = User::factory()->create(['name' => 'Opponent Sample']);
        $otherOpponent = User::factory()->create(['name' => 'Unrelated Sample']);
        $session = OpenPlaySession::create([
            'manager_id' => $organizer->id,
            'organizer_id' => $organizer->id,
            'name' => 'Personal Match History',
            'venue' => 'Community Courts',
            'play_date' => today(),
            'skill_level' => 'all levels',
            'format' => 'doubles',
            'share_token' => str()->random(48),
        ]);
        $court = Court::create(['open_play_session_id' => $session->id, 'name' => 'Court 1']);
        $entries = collect([
            SessionPlayer::create(['open_play_session_id' => $session->id, 'user_id' => $organizer->id]),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'user_id' => $teammate->id]),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'user_id' => $opponent->id]),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'guest_name' => 'Guest History']),
        ]);
        $match = MatchGame::create([
            'open_play_session_id' => $session->id,
            'court_id' => $court->id,
            'status' => 'completed',
            'team_one_score' => 11,
            'team_two_score' => 8,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
        $match->participants()->attach([
            $entries[0]->id => ['team' => 1],
            $entries[1]->id => ['team' => 1],
            $entries[2]->id => ['team' => 2],
            $entries[3]->id => ['team' => 2],
        ]);

        $otherSession = OpenPlaySession::create([
            'manager_id' => $manager->id,
            'name' => 'Another Player Match',
            'venue' => 'Other Courts',
            'play_date' => today(),
            'skill_level' => 'beginner',
            'format' => 'singles',
            'share_token' => str()->random(48),
        ]);
        $otherCourt = Court::create(['open_play_session_id' => $otherSession->id, 'name' => 'Court 2']);
        $otherEntries = collect([
            SessionPlayer::create(['open_play_session_id' => $otherSession->id, 'user_id' => $teammate->id]),
            SessionPlayer::create(['open_play_session_id' => $otherSession->id, 'user_id' => $otherOpponent->id]),
        ]);
        $otherMatch = MatchGame::create([
            'open_play_session_id' => $otherSession->id,
            'court_id' => $otherCourt->id,
            'status' => 'completed',
            'team_one_score' => 11,
            'team_two_score' => 2,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(40),
        ]);
        $otherMatch->participants()->attach([
            $otherEntries[0]->id => ['team' => 1],
            $otherEntries[1]->id => ['team' => 2],
        ]);

        $this->actingAs($organizer)->get(route('players.index'))
            ->assertOk()
            ->assertSee('Players played with')
            ->assertSee('Teammate Sample')
            ->assertSee('Opponent Sample')
            ->assertSee('Guest History')
            ->assertDontSee('Unrelated Sample');
        $this->get(route('players.index', ['search' => 'Teammate']))
            ->assertOk()
            ->assertSee('Teammate Sample')
            ->assertDontSee('Opponent Sample');
    }
}
