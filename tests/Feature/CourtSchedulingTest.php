<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use App\Models\User;
use App\Services\CourtScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_twelve_checked_in_players_fill_two_doubles_courts_and_queue_moves_to_one_freed_court(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'manager_approved' => true,
        ]);
        $players = User::factory()->count(12)->create();
        $session = OpenPlaySession::create([
            'manager_id' => $manager->id,
            'name' => 'Saturday Test Open Play',
            'venue' => 'Test Club',
            'play_date' => today(),
            'skill_level' => 'all levels',
            'format' => 'doubles',
            'share_token' => str()->random(48),
        ]);
        $courts = collect(['Court 1', 'Court 2'])->map(fn ($name, $index) => Court::create([
            'open_play_session_id' => $session->id,
            'name' => $name,
            'sort_order' => $index,
        ]));
        $players->each(fn (User $user, int $index) => SessionPlayer::create([
            'open_play_session_id' => $session->id,
            'user_id' => $user->id,
            'checked_in_at' => now()->subMinutes(12 - $index),
        ]));

        $scheduler = app(CourtScheduler::class);
        $this->assertSame(2, $scheduler->fillOpenCourts($session));
        $session->refresh();
        $active = $session->matches()->where('status', 'playing')->with('participants')->orderBy('court_id')->get();
        $this->assertCount(2, $active);
        $this->assertCount(8, $active->flatMap->participants->unique('id'));
        $this->assertCount(4, $scheduler->waitingPlayers($session));

        $uninterruptedMatch = $active->last();
        $finishedMatch = $active->first();
        $losingPlayer = $finishedMatch->participants->firstWhere('pivot.team', 2)->user;

        $this->actingAs($manager)->post(route('open-plays.score', [$session, $finishedMatch]), [
            'team_one_score' => 11,
            'team_two_score' => 6,
        ])->assertRedirect();

        $this->assertSame('playing', $uninterruptedMatch->fresh()->status);
        $replacement = $session->matches()->where('status', 'playing')->where('court_id', $finishedMatch->court_id)->first();
        $this->assertNotNull($replacement);
        $this->assertNotSame($finishedMatch->id, $replacement->id);
        $this->assertSame(1, $session->matches()->where('status', 'completed')->count());
        $this->assertCount(8, $session->matches()->where('status', 'playing')->with('participants')->get()->flatMap->participants->unique('id'));
        $this->assertCount(0, $session->matches()->where('status', 'playing')->with('participants')->get()
            ->flatMap->participants->pluck('id')->duplicates());

        $this->actingAs($manager)->post(route('open-plays.score', [$session, $finishedMatch]), [
            'team_one_score' => 11,
            'team_two_score' => 6,
        ])->assertSessionHasErrors('score');
        $this->assertSame(1, $session->matches()->where('status', 'completed')->count());
        $this->actingAs($manager)->get(route('open-plays.manage', $session))
            ->assertOk()
            ->assertSee('Waiting queue')
            ->assertSee('Standings');
        $this->get(route('open-plays.live', $session->share_token))
            ->assertOk()
            ->assertSee('Up next')
            ->assertSee('Standings');

        $this->actingAs($losingPlayer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Saturday Test Open Play')
            ->assertSee('11–6')
            ->assertSee('result-loss');
        $this->get(route('open-plays.index'))
            ->assertOk()
            ->assertSee('Your sessions')
            ->assertSee('Saturday Test Open Play');
    }

    public function test_manager_cannot_control_another_managers_session(): void
    {
        $owner = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $otherManager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $session = OpenPlaySession::create([
            'manager_id' => $owner->id,
            'name' => 'Private Session',
            'venue' => 'Test Club',
            'play_date' => today(),
            'skill_level' => 'beginner',
            'format' => 'singles',
            'share_token' => str()->random(48),
        ]);

        $this->actingAs($otherManager)->get(route('open-plays.manage', $session))->assertForbidden();
        $this->put(route('open-plays.update', $session), [
            'name' => 'Hijacked Session',
            'venue' => 'Elsewhere',
            'play_date' => today()->toDateString(),
            'skill_level' => 'advanced',
        ])->assertForbidden();
    }

    public function test_manager_can_add_a_group_of_players_and_guests_when_creating_a_session(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $player = User::factory()->create();

        $this->actingAs($manager)->post(route('open-plays.store'), [
            'name' => 'Evening Open Play',
            'venue' => 'Community Courts',
            'play_date' => today()->toDateString(),
            'skill_level' => 'beginner',
            'format' => 'doubles',
            'courts' => ['East Court', 'West Court'],
            'player_ids' => [$player->id],
            'guest_names' => ['Walk-in Guest'],
        ])->assertRedirect();

        $session = OpenPlaySession::where('name', 'Evening Open Play')->firstOrFail();
        $this->assertDatabaseHas('session_players', ['open_play_session_id' => $session->id, 'user_id' => $player->id]);
        $this->assertDatabaseHas('session_players', ['open_play_session_id' => $session->id, 'guest_name' => 'Walk-in Guest']);
        $this->actingAs($manager)->put(route('open-plays.update', $session), [
            'name' => 'Evening Rally',
            'venue' => 'Northside Courts',
            'play_date' => today()->toDateString(),
            'skill_level' => 'intermediate',
        ])->assertRedirect();
        $this->assertDatabaseHas('open_play_sessions', ['id' => $session->id, 'name' => 'Evening Rally']);
        $this->actingAs($player)->get(route('open-plays.index'))->assertOk()->assertSee('Evening Rally');
    }

    public function test_player_dashboard_includes_guest_teammates_in_partner_stats(): void
    {
        $player = User::factory()->create();
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $session = OpenPlaySession::create([
            'manager_id' => $manager->id,
            'name' => 'Guest Partner Test',
            'venue' => 'Test Club',
            'play_date' => today(),
            'skill_level' => 'all levels',
            'format' => 'doubles',
            'share_token' => str()->random(48),
        ]);
        $court = Court::create(['open_play_session_id' => $session->id, 'name' => 'Court 1']);
        $roster = collect([
            SessionPlayer::create(['open_play_session_id' => $session->id, 'user_id' => $player->id]),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'guest_name' => 'Guest Sky']),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'guest_name' => 'Guest River']),
            SessionPlayer::create(['open_play_session_id' => $session->id, 'guest_name' => 'Guest Blue']),
        ]);
        $match = MatchGame::create([
            'open_play_session_id' => $session->id,
            'court_id' => $court->id,
            'status' => 'completed',
            'team_one_score' => 11,
            'team_two_score' => 4,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
        $match->participants()->attach([
            $roster[0]->id => ['team' => 1],
            $roster[1]->id => ['team' => 1],
            $roster[2]->id => ['team' => 2],
            $roster[3]->id => ['team' => 2],
        ]);

        $this->actingAs($player)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Guest Sky')
            ->assertSee('100%');
    }
}
