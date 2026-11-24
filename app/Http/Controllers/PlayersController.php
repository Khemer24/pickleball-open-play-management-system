<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\SavedPlayerGroup;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PlayersController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $matches = MatchGame::query()
            ->where('status', 'completed')
            ->whereHas('participants', fn ($query) => $query->where('session_players.user_id', $user->id))
            ->with(['participants.user', 'session'])
            ->orderByDesc('completed_at')
            ->get();
        $records = [];

        foreach ($matches as $match) {
            $mine = $match->participants->firstWhere('user_id', $user->id);
            if (! $mine) {
                continue;
            }
            $won = $mine->pivot->team === 1
                ? $match->team_one_score > $match->team_two_score
                : $match->team_two_score > $match->team_one_score;

            foreach ($match->participants as $participant) {
                if ($participant->id === $mine->id) {
                    continue;
                }
                $key = $participant->user_id
                    ? 'user-'.$participant->user_id
                    : 'guest-'.str($participant->displayName())->lower()->squish();
                $records[$key] ??= [
                    'name' => $participant->displayName(),
                    'avatar_path' => $participant->user?->avatar_path,
                    'wins' => 0,
                    'losses' => 0,
                    'recent' => [],
                    'last_played' => $match->completed_at,
                ];
                $records[$key][$won ? 'wins' : 'losses']++;
                $records[$key]['recent'][] = [
                    'won' => $won,
                    'session' => $match->session->name,
                    'date' => $match->completed_at,
                ];
                if ($match->completed_at?->greaterThan($records[$key]['last_played'])) {
                    $records[$key]['last_played'] = $match->completed_at;
                }
            }
        }

        $players = collect($records)->map(function (array $record) {
            $games = $record['wins'] + $record['losses'];
            $record['games'] = $games;
            $record['rate'] = $games === 0 ? 0 : (int) round($record['wins'] / $games * 100);
            $record['recent'] = collect($record['recent'])->take(5);

            return $record;
        });
        $search = trim(request()->string('search')->value());
        if ($search !== '') {
            $players = $players->filter(fn (array $player) => str_contains(mb_strtolower($player['name']), mb_strtolower($search)));
        }

        $sort = request()->query('sort', 'last_played');
        $players = match ($sort) {
            'win_rate' => $players->sort(fn (array $a, array $b) => ($b['rate'] <=> $a['rate']) ?: ($b['games'] <=> $a['games'])),
            'record' => $players->sort(fn (array $a, array $b) => ($b['wins'] <=> $a['wins']) ?: ($a['losses'] <=> $b['losses'])),
            'name' => $players->sortBy(fn (array $player) => mb_strtolower($player['name'])),
            default => $players->sortByDesc(fn (array $player) => $player['last_played']?->getTimestamp() ?? 0),
        };
        $players = $players->values();
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $players->forPage($page, $perPage)->values(),
            $players->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );

        return view('players.index', [
            'players' => $paginated,
            'search' => $search,
            'sort' => $sort,
            'registeredPlayers' => User::where('role', 'player')->orderBy('name')->get(),
            'groups' => $user->isManager()
                ? SavedPlayerGroup::where('manager_id', $user->id)->withCount('players')->orderBy('name')->get()
                : collect(),
        ]);
    }
}
