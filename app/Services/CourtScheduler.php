<?php

namespace App\Services;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourtScheduler
{
    public function fillOpenCourts(OpenPlaySession $session): int
    {
        return DB::transaction(function () use ($session) {
            $lockedSession = OpenPlaySession::query()->lockForUpdate()->findOrFail($session->id);

            if ($lockedSession->finalized_at) {
                return 0;
            }

            if ($lockedSession->organizer_id !== null) {
                return $this->fillOrganizerCourts($lockedSession);
            }

            $started = 0;
            $courts = Court::query()
                ->where('open_play_session_id', $lockedSession->id)
                ->whereDoesntHave('matches', fn ($query) => $query->where('status', 'playing'))
                ->orderBy('sort_order')
                ->lockForUpdate()
                ->get();

            foreach ($courts as $court) {
                $players = $this->availablePlayers($lockedSession);
                $needed = $lockedSession->format === 'doubles' ? 4 : 2;

                if ($players->count() < $needed) {
                    break;
                }

                $selected = $players->take($needed)->values();
                $teams = $lockedSession->format === 'doubles'
                    ? $this->bestDoublesTeams($lockedSession, $selected)
                    : [[$selected[0], $selected[1]], []];

                $match = MatchGame::create([
                    'open_play_session_id' => $lockedSession->id,
                    'court_id' => $court->id,
                    'status' => 'playing',
                    'started_at' => now(),
                ]);

                foreach ($teams as $teamNumber => $teamPlayers) {
                    foreach ($teamPlayers as $player) {
                        $match->participants()->attach($player->id, ['team' => $teamNumber + 1]);
                    }
                }

                $started++;
            }

            return $started;
        }, 3);
    }

    public function waitingPlayers(OpenPlaySession $session): Collection
    {
        $inPlay = DB::table('match_participants')
            ->join('matches', 'matches.id', '=', 'match_participants.match_id')
            ->where('matches.open_play_session_id', $session->id)
            ->where('matches.status', 'playing')
            ->pluck('match_participants.session_player_id');

        return $session->players()
            ->whereNotNull('checked_in_at')
            ->whereNotIn('id', $inPlay)
            ->with('user')
            ->withCount(['matchAppearances as games_played' => fn ($query) => $query->whereHas('match', fn ($match) => $match->where('status', 'completed'))])
            ->orderBy('games_played')
            ->orderBy('checked_in_at')
            ->orderBy('id')
            ->get();
    }

    public function queuedMatches(OpenPlaySession $session): Collection
    {
        return $session->matches()
            ->where('status', 'queued')
            ->with('participants.user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function benchPlayers(OpenPlaySession $session): Collection
    {
        $matched = DB::table('match_participants')
            ->join('matches', 'matches.id', '=', 'match_participants.match_id')
            ->where('matches.open_play_session_id', $session->id)
            ->whereIn('matches.status', ['playing', 'queued'])
            ->pluck('match_participants.session_player_id');

        return $session->players()
            ->whereNotNull('checked_in_at')
            ->whereNotIn('id', $matched)
            ->with('user')
            ->orderBy('checked_in_at')
            ->orderBy('id')
            ->get();
    }

    private function availablePlayers(OpenPlaySession $session): Collection
    {
        return $this->waitingPlayers($session);
    }

    private function fillOrganizerCourts(OpenPlaySession $session): int
    {
        if ($session->scheduling_paused) {
            return 0;
        }

        $openCourts = Court::query()
            ->where('open_play_session_id', $session->id)
            ->whereDoesntHave('matches', fn ($query) => $query->where('status', 'playing'))
            ->orderBy('sort_order')
            ->lockForUpdate()
            ->get();
        $queued = MatchGame::query()
            ->where('open_play_session_id', $session->id)
            ->where('status', 'queued')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $started = 0;

        while ($openCourts->isNotEmpty() && $queued->isNotEmpty()) {
            $queuedMatch = $queued->shift();
            $court = $openCourts->shift();
            $queuedMatch->update([
                'court_id' => $court->id,
                'status' => 'playing',
                'started_at' => now(),
            ]);
            $started++;
        }

        $needed = $session->format === 'doubles' ? 4 : 2;
        while (($players = $this->availableOrganizerPlayers($session))->count() >= $needed) {
            $selected = $players->take($needed)->values();
            $teams = $session->format === 'doubles'
                ? $this->bestDoublesTeams($session, $selected)
                : [[$selected[0], $selected[1]], []];
            $queuedMatch = MatchGame::create([
                'open_play_session_id' => $session->id,
                'court_id' => null,
                'status' => 'queued',
                'started_at' => null,
            ]);

            foreach ($teams as $teamNumber => $teamPlayers) {
                foreach ($teamPlayers as $player) {
                    $queuedMatch->participants()->attach($player->id, ['team' => $teamNumber + 1]);
                }
            }

            if ($openCourts->isNotEmpty()) {
                $court = $openCourts->shift();
                $queuedMatch->update([
                    'court_id' => $court->id,
                    'status' => 'playing',
                    'started_at' => now(),
                ]);
                $started++;
            }
        }

        return $started;
    }

    private function availableOrganizerPlayers(OpenPlaySession $session): Collection
    {
        $matched = DB::table('match_participants')
            ->join('matches', 'matches.id', '=', 'match_participants.match_id')
            ->where('matches.open_play_session_id', $session->id)
            ->whereIn('matches.status', ['playing', 'queued'])
            ->pluck('match_participants.session_player_id');

        return $session->players()
            ->whereNotNull('checked_in_at')
            ->whereNotIn('id', $matched)
            ->with('user')
            ->withCount(['matchAppearances as games_played' => fn ($query) => $query->whereHas('match', fn ($match) => $match->where('status', 'completed'))])
            ->orderBy('games_played')
            ->orderBy('checked_in_at')
            ->orderBy('id')
            ->get();
    }

    private function bestDoublesTeams(OpenPlaySession $session, Collection $players): array
    {
        $ids = $players->pluck('id')->all();
        $history = DB::table('match_participants as first')
            ->join('match_participants as second', function ($join) {
                $join->on('first.match_id', '=', 'second.match_id')
                    ->whereColumn('first.session_player_id', '<', 'second.session_player_id');
            })
            ->join('matches', 'matches.id', '=', 'first.match_id')
            ->where('matches.open_play_session_id', $session->id)
            ->where('matches.status', 'completed')
            ->whereIn('first.session_player_id', $ids)
            ->whereIn('second.session_player_id', $ids)
            ->selectRaw('first.session_player_id as first_id, second.session_player_id as second_id, SUM(CASE WHEN first.team = second.team THEN 5 ELSE 1 END) as pairing_cost')
            ->groupBy('first.session_player_id', 'second.session_player_id')
            ->get()
            ->keyBy(fn ($row) => $row->first_id.':'.$row->second_id);

        $getCost = static function (int $a, int $b) use ($history): int {
            $key = min($a, $b).':'.max($a, $b);
            return (int) ($history->get($key)->pairing_cost ?? 0);
        };

        $arrangements = [
            [[0, 1], [2, 3]],
            [[0, 2], [1, 3]],
            [[0, 3], [1, 2]],
        ];
        $best = $arrangements[0];
        $bestCost = PHP_INT_MAX;

        foreach ($arrangements as $arrangement) {
            $cost = $getCost($players[$arrangement[0][0]]->id, $players[$arrangement[0][1]]->id)
                + $getCost($players[$arrangement[1][0]]->id, $players[$arrangement[1][1]]->id);

            foreach ($arrangement[0] as $left) {
                foreach ($arrangement[1] as $right) {
                    $cost += $getCost($players[$left]->id, $players[$right]->id);
                }
            }

            if ($cost < $bestCost) {
                $bestCost = $cost;
                $best = $arrangement;
            }
        }

        return [
            [$players[$best[0][0]], $players[$best[0][1]]],
            [$players[$best[1][0]], $players[$best[1][1]]],
        ];
    }
}
