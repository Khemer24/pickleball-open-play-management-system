<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\MatchGame;
use App\Models\OpenPlaySession;
use App\Models\SessionPlayer;
use App\Models\User;
use App\Services\CourtScheduler;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class OrganizerEventController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->role === 'organizer', 403);
        $events = $user->organizedSessions()
            ->withCount(['matches as active_matches_count' => fn (Builder $query) => $query->where('status', 'playing')])
            ->withCount('players')
            ->with(['courts', 'organizer'])
            ->orderByDesc('play_date')
            ->get();

        return view('organizer.events.index', [
            'upcoming' => $events->filter(fn (OpenPlaySession $event) => ! $event->finalized_at && $event->active_matches_count === 0)->values(),
            'live' => $events->filter(fn (OpenPlaySession $event) => ! $event->finalized_at && $event->active_matches_count > 0)->values(),
            'completed' => $events->filter(fn (OpenPlaySession $event) => $event->finalized_at)->values(),
        ]);
    }

    public function create(): View
    {
        $this->ensureApprovedOrganizer();

        return view('organizer.events.create', [
            'players' => User::whereIn('role', ['player', 'manager', 'organizer'])->orderBy('name')->get(),
            'managers' => User::where('role', 'manager')->where('manager_approved', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CourtScheduler $scheduler): RedirectResponse
    {
        $this->ensureApprovedOrganizer();
        $data = $this->validatedEvent($request);
        $playerIds = collect($data['player_ids'] ?? []);
        $guestNames = collect($data['guest_names'] ?? [])
            ->flatMap(fn ($names) => preg_split('/\r\n|\r|\n/', (string) $names))
            ->filter(fn ($name) => filled($name))
            ->map(fn ($name) => trim($name))
            ->values();
        if ($guestNames->contains(fn ($name) => mb_strlen($name) > 100)) {
            throw ValidationException::withMessages(['guest_names' => 'Each guest name must be 100 characters or fewer.']);
        }
        $this->ensureWithinLimit($data['player_limit'] ?? null, $playerIds->count() + $guestNames->count());

        $session = DB::transaction(function () use ($data, $playerIds, $guestNames, $request) {
            $organizer = $request->user();
            $session = OpenPlaySession::create([
                'manager_id' => $organizer->id,
                'organizer_id' => $organizer->id,
                'name' => $data['name'],
                'venue' => $data['venue'],
                'play_date' => $data['play_date'],
                'starts_at' => $this->combineSchedule($data['play_date'], $data['start_time'] ?? null),
                'skill_level' => $data['skill_level'],
                'format' => $data['format'],
                'player_limit' => $data['player_limit'] ?? null,
                'rules' => $data['rules'] ?? null,
                'share_token' => str()->random(48),
            ]);

            foreach ($data['courts'] ?? [] as $index => $name) {
                $session->courts()->create(['name' => trim($name), 'sort_order' => $index]);
            }

            foreach ($playerIds as $playerId) {
                $session->players()->create(['user_id' => $playerId]);
            }

            foreach ($guestNames as $guestName) {
                $session->players()->create(['guest_name' => $guestName]);
            }

            DB::transaction(function () use ($session, $data) {
                $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
                $locked->assignedManagers()->sync($data['manager_ids'] ?? []);
            }, 3);

            return $session;
        }, 3);

        $scheduler->fillOpenCourts($session);

        return redirect()->route('events.show', $session)->with('success', 'Event created. Check players in to build the match queue.');
    }

    public function workspace(Request $request, OpenPlaySession $session, CourtScheduler $scheduler): View
    {
        $this->authorize('view', $session);
        abort_if($session->organizer_id === null, 404);
        $session->load([
            'organizer',
            'assignedManagers',
            'players.user',
            'courts.matches' => fn ($query) => $query->where('status', 'playing')->with('participants.user'),
            'matches.participants.user',
            'matches.court',
        ]);

        $completed = $session->matches->where('status', 'completed')->sortByDesc('completed_at')->values();
        $stats = $this->playerStats($session, $completed);
        $tab = $request->query('tab', 'overview');
        abort_unless(in_array($tab, ['overview', 'players', 'courts', 'ranks'], true), 404);
        $selectedPlayer = $request->integer('player');
        if ($selectedPlayer && ! $session->players->contains('id', $selectedPlayer)) {
            abort(404);
        }

        $history = $session->matches()->where('status', 'completed')
            ->with(['participants.user', 'court'])
            ->when($selectedPlayer, fn ($query) => $query->whereHas('participants', fn ($participants) => $participants->where('session_players.id', $selectedPlayer)))
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'history_page')
            ->withQueryString();

        $activeIds = $session->matches->where('status', 'playing')->flatMap->participants->pluck('id')->unique();
        $queued = $scheduler->queuedMatches($session);
        $queuedIds = $queued->flatMap->participants->pluck('id')->unique();

        return view('organizer.events.workspace', [
            'session' => $session,
            'tab' => $tab,
            'canOrganize' => $request->user()->isOrganizer() && $session->organizer_id === $request->user()->id && ! $session->finalized_at,
            'canOperate' => auth()->user()->can('operate', $session),
            'players' => $session->players->sortBy(fn (SessionPlayer $player) => $player->checked_in_at ? 0 : 1)->values(),
            'benchPlayers' => $scheduler->benchPlayers($session),
            'queuedMatches' => $queued,
            'playerStats' => $stats,
            'activePlayerIds' => $activeIds,
            'queuedPlayerIds' => $queuedIds,
            'standings' => $stats->sort(fn (array $a, array $b) => ($b['wins'] <=> $a['wins']) ?: ($b['diff'] <=> $a['diff']))->values(),
            'history' => $history,
            'selectedPlayer' => $selectedPlayer,
            'availablePlayers' => User::whereIn('role', ['player', 'manager', 'organizer'])->orderBy('name')->get(),
            'availableManagers' => User::where('role', 'manager')->where('manager_approved', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('organize', $session);
        $data = $this->validatedEvent($request, creating: false);
        DB::transaction(function () use ($session, $data) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');

            if (isset($data['format']) && $data['format'] !== $locked->format && $locked->matches()->exists()) {
                throw ValidationException::withMessages(['format' => 'The format cannot change after matchups have been created.']);
            }
            if (isset($data['player_limit']) && $data['player_limit'] !== null && $locked->players()->count() > $data['player_limit']) {
                throw ValidationException::withMessages(['player_limit' => 'The player limit cannot be lower than the current roster.']);
            }

            $locked->update([
                ...collect($data)->except(['courts', 'player_ids', 'guest_names', 'manager_ids', 'start_time'])->all(),
                'starts_at' => $this->combineSchedule($data['play_date'], $data['start_time'] ?? null),
            ]);
        }, 3);

        return back()->with('success', 'Event details updated.');
    }

    public function assignManagers(Request $request, OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('organize', $session);
        $data = $request->validate([
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'manager')->where('manager_approved', true)),
            ],
        ]);
        $session->assignedManagers()->sync($data['manager_ids'] ?? []);

        return back()->with('success', 'Assigned managers updated.');
    }

    public function addPlayer(Request $request, OpenPlaySession $session, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('organize', $session);
        $data = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                'required_without:guest_name',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['player', 'manager', 'organizer'])),
            ],
            'guest_name' => ['nullable', 'required_without:user_id', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($session, $data) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
            $alreadyAdded = isset($data['user_id']) && $locked->players()->where('user_id', $data['user_id'])->exists();
            $this->ensureWithinLimit($locked->player_limit, $locked->players()->count() + ($alreadyAdded ? 0 : 1));
            if (isset($data['user_id'])) {
                $locked->players()->firstOrCreate(['user_id' => $data['user_id']]);
            } else {
                $locked->players()->create(['guest_name' => trim($data['guest_name'])]);
            }
        }, 3);
        $scheduler->fillOpenCourts($session);

        return back()->with('success', 'Player added to the event roster.');
    }

    public function setCheckIn(Request $request, OpenPlaySession $session, SessionPlayer $player, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('operate', $session);
        $data = $request->validate(['checked_in' => ['required', 'boolean']]);
        DB::transaction(function () use ($session, $player, $data) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
            $entry = SessionPlayer::whereKey($player->id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->open_play_session_id === $locked->id, 404);

            if (! $data['checked_in'] && $entry->matchAppearances()->whereHas('match', fn (Builder $query) => $query->where('status', 'playing'))->exists()) {
                throw ValidationException::withMessages(['checked_in' => 'A player with an active match cannot check out.']);
            }
            if (! $data['checked_in']) {
                $queuedMatchIds = $entry->matchAppearances()
                    ->whereHas('match', fn (Builder $query) => $query->where('status', 'queued'))
                    ->pluck('match_id');
                MatchGame::whereIn('id', $queuedMatchIds)->delete();
            }

            $entry->update(['checked_in_at' => $data['checked_in'] ? ($entry->checked_in_at ?? now()) : null]);
        }, 3);
        $scheduler->fillOpenCourts($session);

        return back()->with('success', $data['checked_in'] ? 'Player checked in.' : 'Player checked out.');
    }

    public function setFeeStatus(Request $request, OpenPlaySession $session, SessionPlayer $player): RedirectResponse
    {
        $this->authorize('operate', $session);
        $data = $request->validate(['fee_paid' => ['required', 'boolean']]);
        DB::transaction(function () use ($session, $player, $data) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
            $entry = SessionPlayer::whereKey($player->id)->lockForUpdate()->firstOrFail();
            abort_unless($entry->open_play_session_id === $locked->id, 404);
            $entry->update(['fee_paid' => $data['fee_paid']]);
        }, 3);

        return back()->with('success', 'Event fee status updated.');
    }

    public function addCourt(Request $request, OpenPlaySession $session, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('organize', $session);
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        DB::transaction(function () use ($session, $data) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
            if ($locked->courts()->count() >= 12) {
                throw ValidationException::withMessages(['name' => 'An event can have at most 12 courts.']);
            }
            if ($locked->courts()->where('name', trim($data['name']))->exists()) {
                throw ValidationException::withMessages(['name' => 'Court names must be unique within an event.']);
            }
            $locked->courts()->create([
                'name' => trim($data['name']),
                'sort_order' => (int) $locked->courts()->max('sort_order') + 1,
            ]);
        }, 3);
        $scheduler->fillOpenCourts($session);

        return back()->with('success', 'Court added and queued matches were checked.');
    }

    public function toggleScheduling(OpenPlaySession $session, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('organize', $session);
        $paused = DB::transaction(function () use ($session) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->finalized_at, 422, 'Finalized events cannot be changed.');
            $paused = ! $locked->scheduling_paused;
            $locked->update(['scheduling_paused' => $paused]);
            if ($paused) {
                $locked->matches()->where('status', 'queued')->delete();
            }

            return $paused;
        }, 3);
        if (! $paused) {
            $scheduler->fillOpenCourts($session);
        }

        return back()->with('success', $paused
            ? 'New matchups are paused. Active matches can finish before you finalize.'
            : 'Match queue resumed.');
    }

    public function correctScore(Request $request, OpenPlaySession $session, MatchGame $match): RedirectResponse
    {
        $this->authorize('organize', $session);
        abort_unless($match->open_play_session_id === $session->id, 404);
        $data = $request->validate([
            'team_one_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_two_score' => ['required', 'integer', 'min:0', 'max:99', 'different:team_one_score'],
        ]);

        DB::transaction(function () use ($session, $match, $data) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $lockedMatch = MatchGame::whereKey($match->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->finalized_at, 422, 'Finalized event scores are locked.');
            abort_unless($lockedMatch->status === 'completed', 422, 'Only completed matches can be corrected.');
            $lockedMatch->update($data);
        }, 3);

        return back()->with('success', 'Score corrected and standings recalculated.');
    }

    public function score(Request $request, OpenPlaySession $session, MatchGame $match, CourtScheduler $scheduler): RedirectResponse
    {
        $this->authorize('operate', $session);
        abort_unless($match->open_play_session_id === $session->id, 404);
        $data = $request->validate([
            'team_one_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_two_score' => ['required', 'integer', 'min:0', 'max:99', 'different:team_one_score'],
        ]);
        DB::transaction(function () use ($session, $match, $data) {
            $lockedSession = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $lockedMatch = MatchGame::whereKey($match->id)->lockForUpdate()->firstOrFail();
            if ($lockedSession->finalized_at || $lockedMatch->status !== 'playing') {
                throw ValidationException::withMessages(['score' => 'This match has already been completed or the event is finalized.']);
            }
            $lockedMatch->update([...$data, 'status' => 'completed', 'completed_at' => now()]);
        }, 3);
        $scheduler->fillOpenCourts($session);

        return back()->with('success', 'Final score recorded. The next queued match has been started.');
    }

    public function finalize(OpenPlaySession $session): RedirectResponse
    {
        $this->authorize('organize', $session);
        DB::transaction(function () use ($session) {
            $locked = OpenPlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->matches()->whereIn('status', ['playing', 'queued'])->exists(), 422, 'Start or resolve every queued match before finalizing.');
            $locked->update(['finalized_at' => now()]);
        }, 3);

        return back()->with('success', 'Event finalized. Scores and standings are locked.');
    }

    private function validatedEvent(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'venue' => ['required', 'string', 'max:160'],
            'play_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'skill_level' => ['required', 'in:beginner,intermediate,advanced,all levels'],
            'format' => [$creating ? 'required' : 'sometimes', 'in:singles,doubles'],
            'player_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'rules' => ['nullable', 'string', 'max:5000'],
            'courts' => [$creating ? 'nullable' : 'sometimes', 'array', 'max:12'],
            'courts.*' => ['required', 'string', 'max:60', 'distinct'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['player', 'manager', 'organizer'])),
            ],
            'guest_names' => ['nullable', 'array'],
            'guest_names.*' => ['nullable', 'string', 'max:1000'],
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'manager')->where('manager_approved', true)),
            ],
        ]);
    }

    private function combineSchedule(string $date, ?string $time): ?Carbon
    {
        return $time ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}") : null;
    }

    private function ensureApprovedOrganizer(): void
    {
        abort_unless(auth()->user()->isOrganizer(), 403, 'Organizer approval is required to create events.');
    }

    private function ensureWithinLimit(?int $limit, int $playersToAdd): void
    {
        if ($limit !== null && $playersToAdd > $limit) {
            throw ValidationException::withMessages(['player_limit' => 'The event player limit would be exceeded.']);
        }
    }

    private function playerStats(OpenPlaySession $session, Collection $completed): Collection
    {
        return $session->players->map(function (SessionPlayer $player) use ($completed) {
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

            return [
                'player' => $player,
                'wins' => $wins,
                'losses' => $losses,
                'diff' => $diff,
                'games' => $wins + $losses,
            ];
        })->keyBy(fn (array $row) => $row['player']->id);
    }
}
