<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\MatchParticipant;
use App\Models\OpenPlaySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $matches = MatchGame::query()
            ->where('status', 'completed')
            ->whereHas('participants', fn ($query) => $query->where('session_players.user_id', $user->id))
            ->with(['session', 'court', 'participants.user'])
            ->orderByDesc('completed_at')
            ->get();

        $wins = $matches->filter(fn ($match) => $this->wonBy($match, $user->id))->count();
        $losses = $matches->count() - $wins;
        $winRate = $matches->isEmpty() ? 0 : (int) round($wins / $matches->count() * 100);
        $daily = collect(range(6, 0))->map(function (int $offset) use ($matches) {
            $date = today()->subDays($offset);
            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'count' => $matches->filter(fn ($match) => $match->completed_at?->isSameDay($date))->count(),
                'today' => $offset === 0,
            ];
        });

        $sessionCount = $matches->pluck('open_play_session_id')->unique()->count();

        return view('dashboard', [
            'user' => $user,
            'wins' => $wins,
            'losses' => $losses,
            'winRate' => $winRate,
            'openPlays' => $sessionCount,
            'daily' => $daily,
            'weeklyTotal' => $daily->sum('count'),
            'recentMatches' => $matches->take(5),
            'bestTeammates' => $this->relationshipRecords($matches, $user, true),
            'toughestOpponents' => $this->relationshipRecords($matches, $user, false),
            'nextSessions' => OpenPlaySession::query()
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->id))
                ->whereNull('finalized_at')
                ->whereDate('play_date', '>=', today())
                ->orderBy('play_date')
                ->limit(3)
                ->get(),
        ]);
    }

    private function wonBy(MatchGame $match, int $userId): bool
    {
        $player = $match->participants->firstWhere('user_id', $userId);
        return $player && (
            ($player->pivot->team === 1 && $match->team_one_score > $match->team_two_score)
            || ($player->pivot->team === 2 && $match->team_two_score > $match->team_one_score)
        );
    }

    private function relationshipRecords(Collection $matches, User $user, bool $teammates): Collection
    {
        $records = [];

        foreach ($matches as $match) {
            $mine = $match->participants->firstWhere('user_id', $user->id);
            if (! $mine) {
                continue;
            }

            foreach ($match->participants as $participant) {
                if ($participant->id === $mine->id) {
                    continue;
                }

                $isTeammate = $participant->pivot->team === $mine->pivot->team;
                if ($isTeammate !== $teammates) {
                    continue;
                }

                $id = $participant->user_id
                    ? 'user-'.$participant->user_id
                    : 'guest-'.(string) str($participant->displayName())->lower()->squish();
                $records[$id] ??= [
                    'name' => $participant->displayName(),
                    'avatar_path' => $participant->user?->avatar_path,
                    'wins' => 0,
                    'losses' => 0,
                ];
                $won = $this->wonBy($match, $user->id);
                $records[$id][$won ? 'wins' : 'losses']++;
            }
        }

        return collect($records)->map(function (array $record) {
            $games = $record['wins'] + $record['losses'];
            $record['games'] = $games;
            $record['rate'] = $games === 0 ? 0 : (int) round($record['wins'] / $games * 100);
            $record['fraction'] = $games === 0 ? 0 : $record['wins'] / $games;
            return $record;
        })->sort(function (array $a, array $b) use ($teammates) {
            $rateComparison = $a['fraction'] <=> $b['fraction'];
            if ($rateComparison !== 0) {
                return $teammates ? -$rateComparison : $rateComparison;
            }
            return $b['games'] <=> $a['games'];
        })->take(5)->values();
    }
}
