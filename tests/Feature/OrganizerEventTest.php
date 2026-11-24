<?php

namespace Tests\Feature;

use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_checked_in_doubles_players_queue_without_a_court_then_start_and_score_once(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => true]);
        $players = User::factory()->count(5)->create();
        $session = $this->createEvent($organizer, $players);
        $entries = $session->players()->orderBy('id')->get();

        foreach ($entries as $entry) {
            $this->actingAs($organizer)->patch(route('events.players.check-in', [$session, $entry]), [
                'checked_in' => true,
            ])->assertRedirect();
        }

        $queued = $session->matches()->where('status', 'queued')->with('participants')->get();
        $this->assertCount(1, $queued);
        $this->assertCount(4, $queued->first()->participants);
        $this->assertCount(1, app(\App\Services\CourtScheduler::class)->benchPlayers($session));
        $this->assertNull($queued->first()->court_id);
        $this->assertNull($queued->first()->started_at);
        $benchPlayer = app(\App\Services\CourtScheduler::class)->benchPlayers($session)->first();
        $this->actingAs($organizer)->get(route('events.show', $session))
            ->assertOk()
            ->assertSee('Session overview')
            ->assertSee('Share live scoreboard');
        $this->get(route('events.show', [$session, 'tab' => 'players']))->assertOk()->assertSee('Player roster');
        $this->get(route('events.show', [$session, 'tab' => 'courts']))->assertOk()->assertSee('Courts & queue');
        $this->get(route('open-plays.live', $session->share_token))
            ->assertOk()
            ->assertSee('Up next')
            ->assertSee($queued->first()->participants->first()->displayName())
            ->assertSee($benchPlayer->displayName())
            ->assertSee('Courts coming soon');

        $this->actingAs($organizer)->post(route('events.courts.store', $session), ['name' => 'Court A'])
            ->assertRedirect();
        $match = $session->matches()->where('status', 'playing')->firstOrFail();
        $this->assertSame('Court A', $match->court->name);
        $this->assertNotNull($match->started_at);

        $this->post(route('events.matches.score', [$session, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 7,
        ])->assertRedirect();
        $this->post(route('events.matches.score', [$session, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 7,
        ])->assertSessionHasErrors('score');

        $this->assertSame(1, $session->matches()->where('status', 'completed')->count());
        $this->assertCount(4, $match->participants()->get());
        $this->get(route('events.show', [$session, 'tab' => 'ranks']))
            ->assertOk()
            ->assertSee('1–0')
            ->assertSee('0–1')
            ->assertDontSee('2–0');
    }

    public function test_assigned_manager_can_check_in_but_cannot_edit_the_event(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => true]);
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $player = User::factory()->create();
        $session = $this->createEvent($organizer, collect([$player]), [], [$manager->id]);
        $entry = $session->players()->firstOrFail();

        $this->actingAs($manager)->patch(route('events.players.check-in', [$session, $entry]), ['checked_in' => true])
            ->assertRedirect();
        $this->put(route('events.update', $session), [
            'name' => 'Changed by Manager',
            'venue' => 'Test Court',
            'play_date' => today()->toDateString(),
            'skill_level' => 'beginner',
        ])->assertForbidden();
    }

    public function test_checking_out_a_queued_player_rebuilds_the_match_without_them(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => true]);
        $players = User::factory()->count(5)->create();
        $session = $this->createEvent($organizer, $players);
        $entries = $session->players()->orderBy('id')->get();

        foreach ($entries as $entry) {
            $this->actingAs($organizer)->patch(route('events.players.check-in', [$session, $entry]), [
                'checked_in' => true,
            ])->assertRedirect();
        }

        $checkedOut = $entries->first();
        $this->actingAs($organizer)->patch(route('events.players.check-in', [$session, $checkedOut]), [
            'checked_in' => false,
        ])->assertRedirect();

        $queued = $session->matches()->where('status', 'queued')->with('participants')->get();
        $this->assertCount(1, $queued);
        $this->assertCount(4, $queued->first()->participants);
        $this->assertFalse($queued->first()->participants->contains('id', $checkedOut->id));
        $this->assertCount(0, app(\App\Services\CourtScheduler::class)->benchPlayers($session));
    }

    public function test_organizer_can_correct_a_score_before_finalization_only(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'organizer_approved' => true]);
        $players = User::factory()->count(4)->create();
        $session = $this->createEvent($organizer, $players, ['Court 1']);
        foreach ($session->players as $entry) {
            $this->actingAs($organizer)->patch(route('events.players.check-in', [$session, $entry]), ['checked_in' => true]);
        }
        $match = $session->matches()->where('status', 'playing')->firstOrFail();
        $this->actingAs($organizer)->post(route('events.scheduling.toggle', $session))->assertRedirect();
        $this->actingAs($organizer)->post(route('events.matches.score', [$session, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 6,
        ])->assertRedirect();

        $this->put(route('events.matches.correct', [$session, $match]), [
            'team_one_score' => 4,
            'team_two_score' => 11,
        ])->assertRedirect();
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'team_one_score' => 4, 'team_two_score' => 11]);
        $this->get(route('events.show', [$session, 'tab' => 'ranks']))
            ->assertOk()
            ->assertSee('1–0')
            ->assertSee('0–1');

        $this->post(route('events.finalize', $session))->assertRedirect();
        $this->put(route('events.matches.correct', [$session, $match]), [
            'team_one_score' => 11,
            'team_two_score' => 0,
        ])->assertForbidden();
    }

    private function createEvent(User $organizer, iterable $players, array $courts = [], array $managerIds = []): OpenPlaySession
    {
        $playerIds = collect($players)->pluck('id')->all();
        $this->actingAs($organizer)->post(route('events.store'), [
            'name' => 'Organizer Test Event',
            'venue' => 'Community Courts',
            'play_date' => today()->toDateString(),
            'start_time' => '10:00',
            'skill_level' => 'all levels',
            'format' => 'doubles',
            'player_limit' => 12,
            'rules' => 'Have fun.',
            'courts' => $courts,
            'player_ids' => $playerIds,
            'manager_ids' => $managerIds,
        ])->assertRedirect();

        return OpenPlaySession::where('organizer_id', $organizer->id)->where('name', 'Organizer Test Event')->firstOrFail();
    }
}
