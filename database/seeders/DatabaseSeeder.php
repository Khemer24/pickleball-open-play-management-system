<?php

namespace Database\Seeders;

use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SavedPlayerGroup;
use App\Models\SessionPlayer;
use App\Models\User;
use App\Services\CourtScheduler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@rallycourt.test'], [
            'name' => 'RallyCourt Admin',
            'password' => 'password',
            'role' => 'admin',
            'manager_approved' => true,
            'email_verified_at' => now(),
        ]);
        $manager = User::updateOrCreate(['email' => 'manager@rallycourt.test'], [
            'name' => 'Jordan Lee',
            'password' => 'password',
            'role' => 'manager',
            'manager_approved' => true,
            'email_verified_at' => now(),
        ]);
        $organizer = User::updateOrCreate(['email' => 'organizer@rallycourt.test'], [
            'name' => 'Morgan Patel',
            'password' => 'password',
            'role' => 'organizer',
            'organizer_approved' => true,
            'email_verified_at' => now(),
        ]);

        $players = collect(range(1, 12))->map(fn (int $number) => User::updateOrCreate(
            ['email' => sprintf('player%02d@rallycourt.test', $number)],
            [
                'name' => ['Alex Morgan', 'Sam Rivera', 'Taylor Chen', 'Jamie Patel', 'Riley Brooks', 'Casey Nguyen', 'Drew Parker', 'Jordan Kim', 'Avery James', 'Cameron Bell', 'Morgan Ellis', 'Quinn Davis'][$number - 1],
                'password' => 'password',
                'role' => 'player',
                'email_verified_at' => now(),
            ]
        ));

        $session = OpenPlaySession::firstOrCreate(
            ['name' => 'Saturday Community Open Play', 'manager_id' => $manager->id],
            [
                'venue' => 'Riverside Pickleball Club',
                'play_date' => today(),
                'skill_level' => 'all levels',
                'format' => 'doubles',
                'share_token' => Str::random(48),
            ]
        );
        foreach (['Court 1', 'Court 2'] as $index => $courtName) {
            $session->courts()->firstOrCreate(['name' => $courtName], ['sort_order' => $index]);
        }
        foreach ($players as $player) {
            SessionPlayer::firstOrCreate(
                ['open_play_session_id' => $session->id, 'user_id' => $player->id],
                ['checked_in_at' => now()->subMinutes(24 - $player->id % 10)]
            );
        }
        SessionPlayer::firstOrCreate(
            ['open_play_session_id' => $session->id, 'guest_name' => 'Guest Casey'],
            ['checked_in_at' => null]
        );

        $history = OpenPlaySession::firstOrCreate(
            ['name' => 'Wednesday Ladder Night', 'manager_id' => $manager->id],
            [
                'venue' => 'Riverside Pickleball Club',
                'play_date' => today()->subDays(3),
                'skill_level' => 'intermediate',
                'format' => 'doubles',
                'share_token' => Str::random(48),
            ]
        );
        $historyCourt = $history->courts()->firstOrCreate(['name' => 'Center Court'], ['sort_order' => 0]);
        $historyRoster = $players->take(8)->map(fn (User $player) => SessionPlayer::firstOrCreate(
            ['open_play_session_id' => $history->id, 'user_id' => $player->id]
        ));
        $guest = SessionPlayer::firstOrCreate(
            ['open_play_session_id' => $history->id, 'guest_name' => 'Guest Robin']
        );
        $matchups = [
            [[0, 1], [2, 3], 11, 7],
            [[0, 1], [2, 3], 9, 11],
            [[0, 4], [1, 2], 11, 8],
            [[0, 3], [1, 4], 7, 11],
            [[0, 5], [2, 4], 11, 5],
        ];
        if (! $history->matches()->exists()) {
            foreach ($matchups as $number => [$teamOne, $teamTwo, $scoreOne, $scoreTwo]) {
                $match = MatchGame::create([
                    'open_play_session_id' => $history->id,
                    'court_id' => $historyCourt->id,
                    'status' => 'completed',
                    'team_one_score' => $scoreOne,
                    'team_two_score' => $scoreTwo,
                    'started_at' => now()->subDays(3)->addMinutes($number * 30),
                    'completed_at' => now()->subDays(3)->addMinutes($number * 30 + 18),
                ]);
                foreach ($teamOne as $index) {
                    $match->participants()->attach($historyRoster[$index]->id, ['team' => 1]);
                }
                foreach ($teamTwo as $index) {
                    $match->participants()->attach($historyRoster[$index]->id, ['team' => 2]);
                }
            }
        }

        $group = SavedPlayerGroup::firstOrCreate(['manager_id' => $manager->id, 'name' => 'Saturday regulars']);
        $group->players()->syncWithoutDetaching($players->take(6)->pluck('id'));

        app(CourtScheduler::class)->fillOpenCourts($session);

        $organizerEvent = OpenPlaySession::firstOrCreate(
            ['name' => 'Friday Organizer Open Play', 'organizer_id' => $organizer->id],
            [
                'manager_id' => $organizer->id,
                'venue' => 'Riverside Pickleball Club',
                'play_date' => today(),
                'starts_at' => now()->addHour(),
                'skill_level' => 'all levels',
                'format' => 'doubles',
                'player_limit' => 8,
                'rules' => 'Check in at the desk. Games are played to 11, win by 2.',
                'share_token' => Str::random(48),
            ]
        );
        $organizerEvent->assignedManagers()->syncWithoutDetaching([$manager->id]);
        foreach ($players->take(5) as $player) {
            SessionPlayer::firstOrCreate(
                ['open_play_session_id' => $organizerEvent->id, 'user_id' => $player->id],
                ['checked_in_at' => now()]
            );
        }
        app(CourtScheduler::class)->fillOpenCourts($organizerEvent);
    }
}
