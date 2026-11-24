<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SavedPlayerGroup;
use App\Models\SessionPlayer;
use App\Models\User;
use App\Services\CourtScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpenPlayController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['search'] ?? '');
        $filterBySearch = function ($query) use ($search): void {
            if ($search === '') {
                return;
            }

            $query->where(function ($matches) use ($search) {
                $matches->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('manager', fn ($manager) => $manager->where('name', 'like', '%'.$search.'%'));
            });
        };

        $mySessions = OpenPlaySession::query()
            ->where(function ($query) {
                $query->where('manager_id', auth()->id())
                    ->orWhereHas('assignedManagers', fn ($managers) => $managers->where('users.id', auth()->id()))
                    ->orWhereHas('players', fn ($players) => $players->where('user_id', auth()->id()));
            })
            ->tap($filterBySearch)
            ->with(['manager', 'courts', 'players', 'assignedManagers'])
            ->withCount([
                'matches as active_matches_count' => fn ($matches) => $matches->where('status', 'playing'),
                'matches as completed_matches_count' => fn ($matches) => $matches->where('status', 'completed'),
            ])
            ->orderByDesc('play_date')
            ->get();

        $sessions = OpenPlaySession::query()
            ->tap($filterBySearch)
            ->with(['manager', 'courts', 'players', 'assignedManagers'])
            ->withCount([
                'matches as active_matches_count' => fn ($matches) => $matches->where('status', 'playing'),
                'matches as completed_matches_count' => fn ($matches) => $matches->where('status', 'completed'),
            ])
            ->whereNull('finalized_at')
            ->whereDate('play_date', '>=', today())
            ->whereNotIn('id', $mySessions->pluck('id'))
            ->orderBy('play_date')
            ->get();

        return view('open-plays.index', compact('mySessions', 'sessions', 'search'));
    }

    public function show(Request $request, OpenPlaySession $session): View
    {
        $this->authorize('view', $session);

        $tab = $request->query('tab', 'players');
        abort_unless(in_array($tab, ['players', 'courts', 'ranks'], true), 404);

        $session->load([
            'manager',
            'organizer',
            'players.user',
            'courts.matches' => fn ($matches) => $matches->where('status', 'playing')->with('participants.user'),
            'matches.participants.user',
            'matches.court',
        ]);

        $completed = $session->matches->where('status', 'completed')->sortByDesc('completed_at')->values();
        $playerStats = $this->playerStats($session, $completed);
        $standings = $playerStats->sort(fn (array $a, array $b) => ($b['wins'] <=> $a['wins']) ?: ($b['diff'] <=> $a['diff']))->values();
        $activeMatches = $session->matches->where('status', 'playing')->sortBy('court_id')->values();
        $queuedMatches = $session->matches->where('status', 'queued')->sortBy('created_at')->values();
        $activePlayerIds = $activeMatches->flatMap->participants->pluck('id')->unique();
        $queuedPlayerIds = $queuedMatches->flatMap->participants->pluck('id')->unique();
        $benchPlayers = $session->players
            ->filter(fn (SessionPlayer $player) => $player->checked_in_at
                && ! $activePlayerIds->contains($player->id)
                && ! $queuedPlayerIds->contains($player->id))
            ->values();

        $selectedPlayer = $request->integer('player');
        if ($selectedPlayer && ! $session->players->contains('id', $selectedPlayer)) {
            abort(404);
        }

        $history = $session->matches()
            ->where('status', 'completed')
            ->with(['participants.user', 'court'])
            ->when($selectedPlayer, fn ($matches) => $matches->whereHas(
                'participants',
                fn ($participants) => $participants->where('session_players.id', $selectedPlayer),
            ))
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'history_page')
            ->withQueryString();

        return view('open-plays.show', compact(
            'session',
            'tab',
            'playerStats',
            'standings',
            'activeMatches',
            'queuedMatches',
            'activePlayerIds',
            'queuedPlayerIds',
            'benchPlayers',
            'history',
            'selectedPlayer',
        ));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isManager(), 403, 'Manager approval is required to create sessions.');

        return view('open-plays.create', [
            'players' => User::where('role', 'player')->orderBy('name')->get(),
            'groups' => SavedPlayerGroup::where('manager_id', auth()->id())->with('players')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isManager(), 403, 'Manager approval is required to create sessions.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'venue' => ['required', 'string', 'max:160'],
            'play_date' => ['required', 'date'],
            'skill_level' => ['required', 'in:beginner,intermediate,advanced,all levels'],
            'format' => ['required', 'in:singles,doubles'],
            'courts' => ['required', 'array', 'min:1', 'max:12'],
            'courts.*' => ['required', 'string', 'max:60'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'distinct'],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:100'],
        ]);

        $session = DB::transaction(function () use ($data) {
            $session = OpenPlaySession::create([
                ...collect($data)->except(['courts', 'player_ids', 'group_ids', 'guest_names'])->all(),
                'manager_id' => auth()->id(),
                'share_token' => Str::random(48),
            ]);

            foreach ($data['courts'] as $index => $name) {
                $session->courts()->create(['name' => $name, 'sort_order' => $index]);
            }

            $playerIds = collect($data['player_ids'] ?? []);
            $groups = SavedPlayerGroup::where('manager_id', auth()->id())
                ->whereIn('id', $data['group_ids'] ?? [])
                ->with('players:id')
                ->get();
            $playerIds = $playerIds->merge($groups->flatMap(fn ($group) => $group->players->pluck('id')))->unique();

            foreach (User::whereIn('id', $playerIds)->where('role', 'player')->get() as $player) {
                $session->players()->create(['user_id' => $player->id]);
            }

            foreach (collect($data['guest_names'] ?? [])->filter(fn ($name) => filled($name))->unique() as $name) {
                $session->players()->create(['guest_name' => trim($name)]);
            }

            return $session;
        });

        return redirect()->route('open-plays.manage', $session)->with('success', 'Open play created. Add players and check them in to get started.');
    }

    public function manage(OpenPlaySession $session): View
    {
        $this->authorize('manage', $session);
        $session->load(['players.user', 'courts.matches' => fn ($query) => $query->where('status', 'playing')->with('participants.user'), 'matches.participants.user']);

        $gamesPlayed = $session->matches->where('status', 'completed')
            ->flatMap(fn ($match) => $match->participants)
            ->countBy('id');
        $standings = $session->players->map(function ($player) use ($session, $gamesPlayed) {
            $wins = 0;
            $pointsFor = 0;
            $pointsAgainst = 0;
            foreach ($session->matches->where('status', 'completed') as $match) {
                $participant = $match->participants->firstWhere('id', $player->id);
                if (! $participant) {
                    continue;
                }
                $for = $participant->pivot->team === 1 ? $match->team_one_score : $match->team_two_score;
                $against = $participant->pivot->team === 1 ? $match->team_two_score : $match->team_one_score;
                $pointsFor += $for;
                $pointsAgainst += $against;
                $wins += $for > $against ? 1 : 0;
            }
            return [
                'player' => $player,
                'wins' => $wins,
                'games' => $gamesPlayed[$player->id] ?? 0,
                'diff' => $pointsFor - $pointsAgainst,
            ];
        })->sort(fn (array $a, array $b) => ($b['wins'] <=> $a['wins']) ?: ($b['diff'] <=> $a['diff']))->values();

        return view('open-plays.manage', [
            'session' => $session,
            'waiting' => app(CourtScheduler::class)->waitingPlayers($session),
            'standings' => $standings,
            'registeredPlayers' => User::where('role', 'player')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('manage', $session);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'venue' => ['required', 'string', 'max:160'],
            'play_date' => ['required', 'date'],
            'skill_level' => ['required', 'in:beginner,intermediate,advanced,all levels'],
        ]);
        DB::transaction(function () use ($session, $data) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->finalized_at, 422, 'Finalized sessions cannot be changed.');
            $lockedSession->update($data);
        }, 3);

        return back()->with('success', 'Session details updated.');
    }

    public function addPlayer(Request $request, OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('manage', $session);
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_name' => ['nullable', 'string', 'max:100', 'required_without:user_id'],
        ]);

        DB::transaction(function () use ($session, $data) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->finalized_at, 422, 'Finalized sessions cannot be changed.');

            if (! empty($data['user_id'])) {
                $user = User::where('role', 'player')->findOrFail($data['user_id']);
                $lockedSession->players()->firstOrCreate(['user_id' => $user->id]);
            } else {
                $lockedSession->players()->create(['guest_name' => trim($data['guest_name'])]);
            }
        }, 3);

        return back()->with('success', 'Player added to this open play.');
    }

    public function checkIn(OpenPlaySession $session, SessionPlayer $player, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('manage', $session);
        DB::transaction(function () use ($session, $player, $scheduler) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->finalized_at, 422, 'Finalized sessions cannot be changed.');
            $lockedPlayer = SessionPlayer::whereKey($player->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedPlayer->open_play_session_id === $lockedSession->id, 404);
            $lockedPlayer->update(['checked_in_at' => $lockedPlayer->checked_in_at ?? now()]);
            $scheduler->fillOpenCourts($lockedSession);
        }, 3);

        return back()->with('success', 'Check-in saved and available courts updated.');
    }

    public function score(Request $request, OpenPlaySession $session, MatchGame $match, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('manage', $session);
        abort_unless($match->open_play_session_id === $session->id, 404);

        $data = $request->validate([
            'team_one_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_two_score' => ['required', 'integer', 'min:0', 'max:99', 'different:team_one_score'],
        ]);

        DB::transaction(function () use ($session, $match, $data, $scheduler) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $lockedMatch = MatchGame::whereKey($match->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->finalized_at || $lockedMatch->status !== 'playing') {
                throw ValidationException::withMessages(['score' => 'This match has already been completed or the session is finalized.']);
            }

            $lockedMatch->update([
                ...$data,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $scheduler->fillOpenCourts($lockedSession);
        }, 3);

        return back()->with('success', 'Final score recorded. The next available court has been assigned.');
    }

    public function finalize(OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('manage', $session);
        DB::transaction(function () use ($session) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->matches()->where('status', 'playing')->exists(), 422, 'Complete every active match before finalizing.');
            $lockedSession->update(['finalized_at' => now()]);
        }, 3);

        return back()->with('success', 'Session finalized. Scores and podium are now locked.');
    }

    public function live(string $token): View
    {
        $session = OpenPlaySession::where('share_token', $token)
            ->with(['organizer', 'manager', 'courts.matches' => fn ($query) => $query->where('status', 'playing')->with('participants.user'), 'matches.participants.user', 'players.user'])
            ->firstOrFail();
        $completed = $session->matches->where('status', 'completed');
        $scheduler = app(CourtScheduler::class);
        $queuedMatches = $session->organizer_id ? $scheduler->queuedMatches($session) : collect();
        $waiting = $session->organizer_id ? $scheduler->benchPlayers($session) : $scheduler->waitingPlayers($session);

        return view('open-plays.live', [
            'session' => $session,
            'waiting' => $waiting,
            'queuedMatches' => $queuedMatches,
            'queueMode' => $session->organizer_id !== null,
            'queueCount' => $session->organizer_id
                ? $queuedMatches->sum(fn (MatchGame $match) => $match->participants->count())
                : $waiting->count(),
            'activeMatchCount' => $session->matches->where('status', 'playing')->count(),
            'completedMatchCount' => $completed->count(),
            'checkedInCount' => $session->players->whereNotNull('checked_in_at')->count(),
            'standings' => $session->players->map(function ($player) use ($completed) {
                $wins = 0;
                $losses = 0;
                $diff = 0;
                foreach ($completed as $match) {
                    $participant = $match->participants->firstWhere('id', $player->id);
                    if (! $participant) {
                        continue;
                    }
                    $for = $participant->pivot->team === 1 ? $match->team_one_score : $match->team_two_score;
                    $against = $participant->pivot->team === 1 ? $match->team_two_score : $match->team_one_score;
                    $wins += $for > $against ? 1 : 0;
                    $losses += $for < $against ? 1 : 0;
                    $diff += $for - $against;
                }

                return ['player' => $player, 'wins' => $wins, 'losses' => $losses, 'diff' => $diff];
            })->sort(fn (array $a, array $b) => ($b['wins'] <=> $a['wins']) ?: ($b['diff'] <=> $a['diff']))->values(),
        ]);
    }

    private function playerStats(OpenPlaySession $session, Collection $completed): Collection
    {
        $stats = $session->players->mapWithKeys(fn (SessionPlayer $player) => [
            $player->id => [
                'player' => $player,
                'wins' => 0,
                'losses' => 0,
                'diff' => 0,
            ],
        ])->all();

        foreach ($completed as $match) {
            foreach ($match->participants as $player) {
                if (! isset($stats[$player->id])) {
                    continue;
                }

                $for = $player->pivot->team === 1 ? $match->team_one_score : $match->team_two_score;
                $against = $player->pivot->team === 1 ? $match->team_two_score : $match->team_one_score;
                $stats[$player->id]['wins'] += $for > $against ? 1 : 0;
                $stats[$player->id]['losses'] += $for < $against ? 1 : 0;
                $stats[$player->id]['diff'] += $for - $against;
            }
        }

        return collect($stats);
    }
}
