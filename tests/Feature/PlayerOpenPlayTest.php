<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerOpenPlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_find_and_view_their_open_play_sections_and_a_players_match_history(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $players = User::factory()->count(4)->create(['role' => 'player']);
        $session = OpenPlaySession::create([
            'manager_id' => $manager->id,
            'name' => 'Friday Rally',
            'venue' => 'Riverside Courts',
            'play_date' => today(),
            'skill_level' => 'intermediate',
            'format' => 'doubles',
            'share_token' => str()->random(48),
        ]);
        $court = Court::create(['open_play_session_id' => $session->id, 'name' => 'Court 1']);
        $entries = $players->map(fn (User $player) => SessionPlayer::create([
            'open_play_session_id' => $session->id,
            'user_id' => $player->id,
            'checked_in_at' => now()->subMinutes(30),
            'fee_paid' => true,
        ]));
        $match = MatchGame::create([
            'open_play_session_id' => $session->id,
            'court_id' => $court->id,
            'status' => 'completed',
            'team_one_score' => 11,
            'team_two_score' => 7,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(5),
        ]);
        $match->participants()->attach([
            $entries[0]->id => ['team' => 1],
            $entries[1]->id => ['team' => 1],
            $entries[2]->id => ['team' => 2],
            $entries[3]->id => ['team' => 2],
        ]);

        $this->actingAs($players[0])->get(route('open-plays.index', ['search' => 'Friday']))
            ->assertOk()
            ->assertSee('Open plays you’ve played')
            ->assertSee('Friday Rally')
            ->assertSee('View open play');
        $this->get(route('open-plays.index', ['search' => $manager->name]))
            ->assertOk()
            ->assertSee('Friday Rally');

        $this->get(route('open-plays.show', $session))
            ->assertOk()
            ->assertSee('Checked in players')
            ->assertSee('On bench')
            ->assertSee('Paid');

        $this->get(route('open-plays.show', [$session, 'tab' => 'courts']))
            ->assertOk()
            ->assertSee('Court 1')
            ->assertSee('Courts');

        $this->get(route('open-plays.show', [$session, 'tab' => 'ranks']))
            ->assertOk()
            ->assertSee('Ranks')
            ->assertSee('Match history')
            ->assertSee('11')
            ->assertSee('Winner');

        $this->get(route('open-plays.show', [$session, 'tab' => 'ranks', 'player' => $entries[0]->id]))
            ->assertOk()
            ->assertSee('match history in this open play')
            ->assertSee($players[0]->name)
            ->assertSee('Started');
    }

    public function test_player_cannot_view_an_open_play_they_do_not_belong_to(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'manager_approved' => true]);
        $player = User::factory()->create();
        $session = OpenPlaySession::create([
            'manager_id' => $manager->id,
            'name' => 'Private roster',
            'venue' => 'Riverside Courts',
            'play_date' => today(),
            'skill_level' => 'beginner',
            'format' => 'singles',
            'share_token' => str()->random(48),
        ]);

        $this->actingAs($player)->get(route('open-plays.show', $session))->assertForbidden();
    }
}
