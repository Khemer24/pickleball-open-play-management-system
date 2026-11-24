@extends('layouts.event-workspace')
@section('title', $session->name)
@section('content')
@php($activeMatchCount = $session->matches->where('status', 'playing')->count())
@php($completedMatchCount = $session->matches->where('status', 'completed')->count())
<header class="relative isolate mb-5 overflow-hidden rounded-2xl border border-[#a9d894] bg-[#bce99e] px-5 py-5 sm:px-7 sm:py-7 lg:min-h-44">
    <div class="absolute inset-y-0 right-0 hidden w-[44%] overflow-hidden md:block" aria-hidden="true">
        <div class="absolute inset-0 bg-[radial-gradient(#83ba72_1px,transparent_1px)] [background-size:12px_12px] opacity-30"></div>
        <svg class="absolute -right-5 -top-8 h-56 w-[390px] rotate-[-12deg] opacity-90" viewBox="0 0 360 200" fill="none">
            <path d="M45 34 306 23l24 142-264 12L45 34Z" fill="#28664E" stroke="#28664E" stroke-width="8" stroke-linejoin="round"/>
            <path d="m66 49 222-9 17 111-224 10L66 49Z" stroke="#D8ED9F" stroke-width="2"/>
            <path d="m179 44 8 111M66 103l236-10M113 47l10 117m128-122 10 117" stroke="#D8ED9F" stroke-width="2"/>
        </svg>
        <span class="absolute bottom-5 right-10 grid size-14 place-items-center rounded-full bg-lime shadow-lg">
            <span class="grid grid-cols-3 gap-1"><i class="size-1.5 rounded-full bg-pine-mid"></i><i class="size-1.5 rounded-full bg-pine-mid"></i><i class="size-1.5 rounded-full bg-pine-mid"></i><i class="size-1.5 rounded-full bg-pine-mid"></i><i class="size-1.5 rounded-full bg-pine-mid"></i></span>
        </span>
    </div>
    <div class="relative max-w-full md:max-w-[55%]">
        <div class="flex flex-wrap items-center gap-2">
            @if($session->finalized_at)
                <span class="inline-flex items-center gap-1.5 rounded-md border border-pine/15 bg-white/45 px-2 py-1 text-[9px] font-extrabold uppercase tracking-wider text-pine"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completed</span>
            @elseif($activeMatchCount)
                <span class="inline-flex items-center gap-1.5 rounded-md border border-pine/15 bg-white/45 px-2 py-1 text-[9px] font-extrabold uppercase tracking-wider text-pine"><span class="size-1.5 rounded-full bg-[#2f8050]" aria-hidden="true"></span> Live session</span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-md border border-pine/15 bg-white/45 px-2 py-1 text-[9px] font-extrabold uppercase tracking-wider text-pine"><i class="bi bi-calendar-check" aria-hidden="true"></i> Upcoming session</span>
            @endif
            <span class="text-[9px] font-extrabold uppercase tracking-[.13em] text-pine">{{ ucfirst($session->skill_level) }} {{ ucfirst($session->format) }}</span>
        </div>
        <h1 class="mt-3 break-words text-3xl font-extrabold leading-[1.05] tracking-[-.045em] text-pine-deep sm:text-4xl lg:text-5xl">{{ $session->name }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] font-medium text-pine">
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $session->venue }}</span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $session->starts_at?->format('M j, Y · g:i A') ?? $session->play_date->format('M j, Y') }}</span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-people" aria-hidden="true"></i>{{ $session->format === 'doubles' ? '2 vs 2' : '1 vs 1' }}</span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-person-badge" aria-hidden="true"></i>Organized by {{ $session->organizer?->name ?? 'RallyCourt Organizer' }}</span>
        </div>
    </div>
</header>
@if($session->rules)<details class="mb-5 rounded-xl border border-court-line bg-pine-soft/55 px-4 py-3"><summary class="cursor-pointer text-xs font-bold text-pine"><i class="bi bi-card-text mr-1.5" aria-hidden="true"></i>Event rules</summary><p class="mt-2 whitespace-pre-line text-[11px] leading-5 text-muted">{{ $session->rules }}</p></details>@endif

@if($tab === 'overview')
    <div class="mb-5">
        <div class="mb-3"><h2 class="text-lg font-extrabold tracking-tight text-pine">Session overview</h2><p class="mt-0.5 text-xs text-muted">A live snapshot of your open play.</p></div>
        <section aria-label="Session overview" class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <article class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card"><div class="flex items-center gap-2 text-[11px] font-semibold text-muted"><span class="grid size-8 place-items-center rounded-lg bg-pine-soft text-pine"><i class="bi bi-people" aria-hidden="true"></i></span>Players checked in</div><p class="mt-2 text-2xl font-extrabold text-pine">{{ $session->players->whereNotNull('checked_in_at')->count() }}<span class="ml-1 text-xs font-medium text-muted">/ {{ $session->players->count() }}</span></p></article>
            <article class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card"><div class="flex items-center gap-2 text-[11px] font-semibold text-muted"><span class="grid size-8 place-items-center rounded-lg bg-pine-soft text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i></span>Courts live</div><p class="mt-2 text-2xl font-extrabold text-pine">{{ $activeMatchCount }}<span class="ml-1 text-xs font-medium text-muted">/ {{ $session->courts->count() }}</span></p></article>
            <article class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card"><div class="flex items-center gap-2 text-[11px] font-semibold text-muted"><span class="grid size-8 place-items-center rounded-lg bg-[#eff4df] text-pine"><i class="bi bi-lightning" aria-hidden="true"></i></span>Queued matches</div><p class="mt-2 text-2xl font-extrabold text-pine">{{ $queuedMatches->count() }}</p></article>
            <article class="rounded-xl border border-line bg-surface px-4 py-3.5 shadow-card"><div class="flex items-center gap-2 text-[11px] font-semibold text-muted"><span class="grid size-8 place-items-center rounded-lg bg-pine-soft text-pine"><i class="bi bi-trophy" aria-hidden="true"></i></span>Completed matches</div><p class="mt-2 text-2xl font-extrabold text-pine">{{ $completedMatchCount }}</p></article>
        </section>
    </div>

    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.85fr)]">
        <section class="min-w-0 rounded-xl border border-line bg-surface shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5 sm:px-5">
                <div><h2 class="text-sm font-extrabold text-pine">Live courts <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $activeMatchCount }}</span></h2><p class="mt-0.5 text-[10px] text-muted">Active games and court availability</p></div>
                <a class="btn-outline min-h-8 px-2.5 text-[10px]" href="{{ route('events.show', [$session, 'tab' => 'courts']) }}">Courts & queue <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="grid gap-3 p-3 sm:grid-cols-2 sm:p-4">
                @forelse($session->courts as $court)
                    @php($match = $court->matches->first())
                    <article class="overflow-hidden rounded-lg border border-[#dce7d8] bg-white">
                        <div class="flex items-center justify-between gap-2 bg-[#eef4e8] px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i>{{ $court->name }}</span>
                            @if($match)<span class="font-mono text-[9px] font-bold text-pine-mid" data-start="{{ $match->started_at?->timestamp }}">{{ $match->started_at?->format('H:i') }}</span>@else<span class="ui-badge px-2 py-0.5 text-[9px]">Available</span>@endif
                        </div>
                        @if($match)
                            @foreach([1, 2] as $team)
                                <div class="flex items-center justify-between gap-2 border-b border-line px-3 py-2 last:border-b-0">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="flex -space-x-1.5">@foreach($match->participants->where('pivot.team', $team) as $player)<span class="grid size-6 place-items-center rounded-full border-2 border-white bg-pine-soft text-[8px] font-bold text-pine">{{ strtoupper(substr($player->displayName(), 0, 1)) }}</span>@endforeach</span>
                                        <span class="truncate text-[10px] font-semibold text-pine">{{ $match->participants->where('pivot.team', $team)->map(fn ($player) => $player->displayName())->join(' & ') }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-muted">T{{ $team }}</span>
                                </div>
                            @endforeach
                        @else
                            <p class="px-3 py-5 text-center text-[10px] text-muted">Ready for the next match</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-court-line bg-pine-soft/40 px-4 py-7 text-center sm:col-span-2"><i class="bi bi-grid-3x3 text-xl text-pine-mid" aria-hidden="true"></i><p class="mt-2 text-xs font-semibold text-pine">No courts added yet</p><p class="mt-1 text-[10px] text-muted">Add a court in Courts & queue to start a matchup.</p></div>
                @endforelse
            </div>
        </section>

        <section class="min-w-0 rounded-xl border border-line bg-surface shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5">
                <div><h2 class="text-sm font-extrabold text-pine">Match queue <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $queuedMatches->count() }}</span></h2><p class="mt-0.5 text-[10px] text-muted">Next eligible matchups</p></div>
                <a class="text-[10px] font-bold text-pine hover:text-pine-mid" href="{{ route('events.show', [$session, 'tab' => 'courts']) }}">Manage <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="divide-y divide-line">
                @forelse($queuedMatches->take(3) as $index => $match)
                    @php($teamOne = $match->participants->where('pivot.team', 1))
                    @php($teamTwo = $match->participants->where('pivot.team', 2))
                    <div class="flex items-center gap-3 px-4 py-3">
                        <span @class(['grid size-7 shrink-0 place-items-center rounded-md text-[9px] font-extrabold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index > 0])>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-[10px] font-bold text-pine">{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</p><p class="mt-0.5 truncate text-[9px] text-muted">vs {{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</p></div>
                        @if($index === 0)<span class="rounded-full bg-pine-soft px-2 py-1 text-[8px] font-bold text-pine">Up next</span>@endif
                    </div>
                @empty
                    <p class="px-4 py-7 text-center text-[10px] text-muted">No queued matches right now.</p>
                @endforelse
            </div>
            <div class="flex items-center gap-2 border-t border-line px-4 py-3 text-[10px] text-muted"><i class="bi bi-person-walking text-pine-mid" aria-hidden="true"></i><strong class="text-pine">Bench · {{ $benchPlayers->count() }}</strong> players waiting for a matchup</div>
        </section>
    </div>

    <section class="mt-4 rounded-xl border border-line bg-surface shadow-card">
        <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5 sm:px-5"><div><h2 class="text-sm font-extrabold text-pine">Recent results</h2><p class="mt-0.5 text-[10px] text-muted">Completed matches from this event</p></div><a class="text-[10px] font-bold text-pine hover:text-pine-mid" href="{{ route('events.show', [$session, 'tab' => 'ranks']) }}">View all history <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
        <div class="divide-y divide-line">
            @forelse($history->items() as $match)
                @php($teamOne = $match->participants->where('pivot.team', 1))
                @php($teamTwo = $match->participants->where('pivot.team', 2))
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
                    <span class="min-w-16 text-[9px] font-bold text-pine">{{ $match->court?->name ?? 'Match' }}<span class="mt-0.5 block font-normal text-muted">{{ $match->completed_at?->format('g:i A') }}</span></span>
                    <div class="min-w-0 flex-1 text-[10px] leading-5"><strong @class(['font-semibold', 'text-pine' => $match->team_one_score > $match->team_two_score, 'text-muted' => $match->team_one_score < $match->team_two_score])>{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</strong><span class="px-1 text-muted">vs</span><strong @class(['font-semibold', 'text-pine' => $match->team_two_score > $match->team_one_score, 'text-muted' => $match->team_two_score < $match->team_one_score])>{{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</strong></div>
                    <span class="rounded-md bg-pine-soft px-2.5 py-1 text-[10px] font-extrabold text-pine">{{ $match->team_one_score }}–{{ $match->team_two_score }}</span>
                </div>
            @empty
                <p class="px-5 py-6 text-center text-[10px] text-muted">Final scores will appear here after the first match is completed.</p>
            @endforelse
        </div>
    </section>

    @if($canOrganize)
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            <details class="rounded-xl border border-line bg-surface p-4 shadow-card">
                <summary class="cursor-pointer text-xs font-bold text-pine"><i class="bi bi-sliders2 mr-1.5" aria-hidden="true"></i>Edit event details</summary>
                <form method="POST" action="{{ route('events.update', $session) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
                    <div><label class="field-label" for="edit-name">Event name</label><input id="edit-name" class="field" name="name" value="{{ $session->name }}" required></div>
                    <div><label class="field-label" for="edit-venue">Venue</label><input id="edit-venue" class="field" name="venue" value="{{ $session->venue }}" required></div>
                    <div><label class="field-label" for="edit-date">Date</label><input id="edit-date" class="field" type="date" name="play_date" value="{{ $session->play_date->toDateString() }}" required></div>
                    <div><label class="field-label" for="edit-time">Start time</label><input id="edit-time" class="field" type="time" name="start_time" value="{{ $session->starts_at?->format('H:i') }}"></div>
                    <div><label class="field-label" for="edit-skill">Skill level</label><select id="edit-skill" class="field" name="skill_level">@foreach(['beginner','intermediate','advanced','all levels'] as $level)<option value="{{ $level }}" @selected($session->skill_level === $level)>{{ ucfirst($level) }}</option>@endforeach</select></div>
                    <div><label class="field-label" for="edit-format">Format</label><select id="edit-format" class="field" name="format" @disabled($session->matches->isNotEmpty())>@foreach(['doubles','singles'] as $format)<option value="{{ $format }}" @selected($session->format === $format)>{{ ucfirst($format) }}</option>@endforeach</select></div>
                    <div><label class="field-label" for="edit-limit">Player limit</label><input id="edit-limit" class="field" type="number" min="1" max="200" name="player_limit" value="{{ $session->player_limit }}"></div>
                    <div class="sm:col-span-2"><label class="field-label" for="edit-rules">Rules</label><textarea id="edit-rules" class="field min-h-20" name="rules" maxlength="5000">{{ $session->rules }}</textarea></div>
                    <button class="btn-primary sm:col-span-2">Save event details</button>
                </form>
            </details>
            <details class="rounded-xl border border-line bg-surface p-4 shadow-card">
                <summary class="cursor-pointer text-xs font-bold text-pine"><i class="bi bi-person-gear mr-1.5" aria-hidden="true"></i>Assign Managers <span class="font-normal text-muted">· {{ $session->assignedManagers->count() }} assigned</span></summary>
                <form method="POST" action="{{ route('events.managers.update', $session) }}" class="mt-3">@csrf
                    <div class="max-h-44 overflow-y-auto rounded-xl border border-line p-2">@forelse($availableManagers as $manager)<label class="flex cursor-pointer items-center gap-2 border-b border-line px-2 py-2 text-xs last:border-0"><input class="accent-pine-mid" type="checkbox" name="manager_ids[]" value="{{ $manager->id }}" @checked($session->assignedManagers->contains('id', $manager->id))><span class="flex-1">{{ $manager->name }}</span>@if($session->assignedManagers->contains('id', $manager->id))<span class="badge-success">Assigned</span>@endif</label>@empty<p class="p-2 text-xs text-muted">No approved Managers available.</p>@endforelse</div>
                    <button class="btn-secondary mt-3">Save assignments</button>
                </form>
            </details>
        </div>
    @endif
@elseif($tab === 'players')
    <section class="rounded-xl border border-line bg-surface shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div><h2 class="text-sm font-extrabold text-pine">Player roster <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $players->count() }}</span></h2><p class="mt-1 text-[10px] text-muted">Check-in, event fees, and match status</p></div>
            @if($canOrganize)
                <details class="relative">
                    <summary class="btn-primary min-h-9 cursor-pointer px-3 text-[10px]"><i class="bi bi-person-plus" aria-hidden="true"></i>Add player</summary>
                    <div class="absolute right-0 z-10 mt-2 w-[min(90vw,440px)] rounded-xl border border-line bg-surface p-4 shadow-xl">
                        <h3 class="text-xs font-bold text-pine">Add to roster</h3>
                        <p class="mt-1 text-[10px] text-muted">Add a registered player or a guest by name.</p>
                        <form method="POST" action="{{ route('events.players.store', $session) }}" class="mt-3 grid gap-3">@csrf
                            <div><label class="field-label" for="add-user">Registered player</label><select id="add-user" class="field" name="user_id"><option value="">Choose a player…</option>@foreach($availablePlayers as $person)<option value="{{ $person->id }}">{{ $person->name }} · {{ $person->email }}</option>@endforeach</select></div>
                            <div><label class="field-label" for="add-guest">Or add a guest</label><input id="add-guest" class="field" name="guest_name" maxlength="100" placeholder="Guest name"></div>
                            <button class="btn-primary">Add to roster</button>
                        </form>
                    </div>
                </details>
            @endif
        </div>
        <div class="flex flex-col gap-2 border-b border-line bg-[#fbfbf7] p-3 sm:flex-row sm:items-center">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Search players</span><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                <input class="field min-h-9 pl-9 text-xs" type="search" placeholder="Search players" data-roster-search>
            </label>
            <label><span class="sr-only">Filter players by attendance</span><select class="field min-h-9 min-w-36 py-1.5 text-xs" data-roster-filter><option value="">All players</option><option value="checked-in">Checked in</option><option value="not-checked-in">Not checked in</option></select></label>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-xs">
                <thead class="bg-[#f3f5ed] text-[9px] font-bold uppercase tracking-wider text-muted"><tr><th scope="col" class="px-4 py-3">Player</th><th scope="col" class="px-4 py-3">Session status</th><th scope="col" class="px-4 py-3">Event fee</th><th scope="col" class="px-4 py-3">Record</th><th scope="col" class="px-4 py-3 text-right">Attendance</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse($players as $player)
                        @php($stats = $playerStats[$player->id])
                        @php($status = $activePlayerIds->contains($player->id) ? 'On court' : ($queuedPlayerIds->contains($player->id) ? 'Up next' : ($player->checked_in_at ? 'On bench' : 'Not checked in')))
                        @php($statusKey = $player->checked_in_at ? 'checked-in' : 'not-checked-in')
                        <tr class="hover:bg-[#fbfcf8]" data-roster-row data-player-name="{{ mb_strtolower($player->displayName()) }}" data-player-status="{{ $statusKey }}">
                            <th scope="row" class="px-4 py-3 font-semibold text-pine"><span class="flex items-center gap-2.5"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-pine-soft text-[10px] font-bold text-pine">{{ strtoupper(substr($player->displayName(), 0, 1)) }}</span><span class="min-w-0"><span class="block truncate">{{ $player->displayName() }}</span>@if(!$player->user_id)<span class="mt-0.5 block text-[9px] font-normal text-muted">Guest player</span>@endif</span></span></th>
                            <td class="px-4 py-3">@if($status === 'On court')<span class="live-pill">On court</span>@elseif($status === 'Up next')<span class="ui-badge bg-[#edf4d8] text-pine">Up next</span>@elseif($status === 'On bench')<span class="ui-badge">On bench</span>@else<span class="text-[10px] text-muted">Not checked in</span>@endif</td>
                            <td class="px-4 py-3">
                                @if($canOperate)
                                    <form method="POST" action="{{ route('events.players.fee', [$session, $player]) }}">@csrf @method('PATCH')<input type="hidden" name="fee_paid" value="{{ $player->fee_paid ? 0 : 1 }}"><button class="{{ $player->fee_paid ? 'badge-success' : 'badge-warning' }}" aria-label="Mark {{ $player->displayName() }} fee {{ $player->fee_paid ? 'unpaid' : 'paid' }}">{{ $player->fee_paid ? 'Paid' : 'Unpaid' }}</button></form>
                                @else
                                    <span class="{{ $player->fee_paid ? 'badge-success' : 'badge-warning' }}">{{ $player->fee_paid ? 'Paid' : 'Unpaid' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-muted">{{ $stats['wins'] }}W–{{ $stats['losses'] }}L</td>
                            <td class="px-4 py-3 text-right">
                                @if($canOperate && $player->checked_in_at)
                                    <form method="POST" action="{{ route('events.players.check-in', [$session, $player]) }}">@csrf @method('PATCH')<input type="hidden" name="checked_in" value="0"><button class="btn-outline min-h-8 px-2.5 py-1 text-[10px]" @disabled($status === 'On court') aria-label="Check out {{ $player->displayName() }}">Check out</button></form>
                                @elseif($canOperate)
                                    <form method="POST" action="{{ route('events.players.check-in', [$session, $player]) }}">@csrf @method('PATCH')<input type="hidden" name="checked_in" value="1"><button class="btn-outline min-h-8 px-2.5 py-1 text-[10px]" aria-label="Check in {{ $player->displayName() }}">Check in</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center"><i class="bi bi-people text-2xl text-pine-mid" aria-hidden="true"></i><p class="mt-2 text-xs font-semibold text-pine">No players on the roster</p><p class="mt-1 text-[10px] text-muted">Add registered players or guests to get started.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="border-t border-line px-4 py-2.5 text-[9px] text-muted">Showing <span data-roster-visible-count>{{ $players->count() }}</span> of {{ $players->count() }} rostered players</p>
    </section>
@elseif($tab === 'courts')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-lg font-extrabold text-pine">Courts & queue</h2><p class="mt-1 text-xs text-muted">Active matches, queued matchups, and the bench</p></div>
        <span class="ui-badge"><span class="size-1.5 rounded-full bg-[#3a8955]" aria-hidden="true"></span>{{ $activeMatchCount }} courts live</span>
    </div>
    @if($canOrganize && !$session->finalized_at)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-court-line bg-pine-soft px-4 py-3">
            <div><strong class="block text-xs text-pine">{{ $session->scheduling_paused ? 'Match queue paused' : 'Match queue running' }}</strong><span class="text-[10px] text-muted">{{ $session->scheduling_paused ? 'Active matches can finish without new games starting.' : 'The next eligible match will start when a court is free.' }}</span></div>
            <form method="POST" action="{{ route('events.scheduling.toggle', $session) }}">@csrf<button class="btn-secondary min-h-9 px-3 text-xs">{{ $session->scheduling_paused ? 'Resume queue' : 'Pause queue' }}</button></form>
        </div>
    @endif
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.85fr)]">
        <section class="min-w-0 overflow-hidden rounded-xl border border-line bg-surface shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5 sm:px-5"><div><h3 class="text-sm font-extrabold text-pine">Live courts <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $activeMatchCount }}</span></h3><p class="mt-0.5 text-[10px] text-muted">Scores and match timer</p></div>@if($canOrganize)<span class="text-[10px] text-muted">{{ $session->courts->count() }} courts added</span>@endif</div>
            @if($session->courts->isEmpty())<div class="px-4 py-8 text-center"><i class="bi bi-grid-3x3 text-2xl text-pine-mid" aria-hidden="true"></i><p class="mt-2 text-xs font-semibold text-pine">No courts yet</p><p class="mx-auto mt-1 max-w-sm text-[10px] leading-5 text-muted">Matchups stay queued until a court is added. You can add a court at any time.</p></div>@endif
            <div class="grid gap-3 p-3 sm:grid-cols-2 sm:p-4">
                @foreach($session->courts as $court)
                    @php($match = $court->matches->first())
                    <article class="overflow-hidden rounded-lg border border-[#dce7d8] bg-white">
                        <div class="flex items-center justify-between gap-2 bg-[#eef4e8] px-3 py-2.5"><span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i>{{ $court->name }}</span>@if($match)<span class="inline-flex items-center gap-1.5 text-[9px] font-bold text-pine-mid"><span class="size-1.5 rounded-full bg-[#3a8955]"></span>LIVE</span>@else<span class="ui-badge px-2 py-0.5 text-[9px]">Available</span>@endif</div>
                        @if($match)
                            @foreach([1, 2] as $team)
                                <div class="flex items-center justify-between gap-2 border-b border-line px-3 py-2.5 last:border-0">
                                    <div class="min-w-0"><span class="mb-1 block text-[8px] font-bold uppercase tracking-wider text-muted">Team {{ $team }}</span><span class="block truncate text-[10px] font-semibold text-pine">{{ $match->participants->where('pivot.team', $team)->map(fn ($player) => $player->displayName())->join(' & ') }}</span></div>
                                    <span class="grid size-7 place-items-center rounded-md bg-pine-soft text-[9px] font-bold text-pine">{{ $team }}</span>
                                </div>
                            @endforeach
                            <div class="flex items-center justify-between border-t border-line px-3 py-2 text-[9px] text-muted"><span>Started {{ $match->started_at?->format('g:i A') }}</span><span class="rounded bg-[#eff4df] px-2 py-1 font-mono font-bold text-pine" data-live-timer="{{ $match->started_at?->timestamp }}" aria-label="Match elapsed time">00:00</span></div>
                            @if($canOperate)
                                <form method="POST" action="{{ route('events.matches.score', [$session, $match]) }}" class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-end gap-2 border-t border-line p-3">@csrf
                                    <div><label class="field-label mb-1 text-[9px]" for="score-one-{{ $match->id }}">Team 1</label><input id="score-one-{{ $match->id }}" class="field min-h-9 px-2 text-xs" type="number" name="team_one_score" min="0" max="99" placeholder="Score" required></div><span class="pb-2 text-muted">–</span>
                                    <div><label class="field-label mb-1 text-[9px]" for="score-two-{{ $match->id }}">Team 2</label><input id="score-two-{{ $match->id }}" class="field min-h-9 px-2 text-xs" type="number" name="team_two_score" min="0" max="99" placeholder="Score" required></div><button class="btn-primary min-h-9 px-2.5 text-[10px]">Save</button>
                                </form>
                            @endif
                        @else
                            <div class="flex min-h-24 flex-col items-center justify-center px-3 py-5 text-center"><span class="grid size-8 place-items-center rounded-full bg-pine-soft text-pine"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span><p class="mt-2 text-[10px] font-semibold text-pine">Ready for the next match</p></div>
                        @endif
                    </article>
                @endforeach
            </div>
            @if($canOrganize)
                <form method="POST" action="{{ route('events.courts.store', $session) }}" class="flex flex-wrap items-end gap-2 border-t border-line bg-[#fbfbf7] p-3 sm:px-4">@csrf
                    <div class="min-w-[180px] flex-1"><label class="field-label" for="new-court">Add a court</label><input class="field min-h-9 text-xs" id="new-court" name="name" placeholder="Court {{ $session->courts->count() + 1 }}" maxlength="60" required></div><button class="btn-secondary min-h-9 px-3 text-xs"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add court</button>
                </form>
            @endif
        </section>

        <div class="space-y-4">
            <section class="overflow-hidden rounded-xl border border-line bg-surface shadow-card">
                <div class="flex items-center justify-between border-b border-line px-4 py-3.5"><div><h3 class="text-sm font-extrabold text-pine">Match queue <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $queuedMatches->count() }}</span></h3><p class="mt-0.5 text-[10px] text-muted">Waiting for an open court</p></div><i class="bi bi-list-ol text-lg text-pine-mid" aria-hidden="true"></i></div>
                <div class="divide-y divide-line">
                    @forelse($queuedMatches as $index => $match)
                        @php($teamOne = $match->participants->where('pivot.team', 1))
                        @php($teamTwo = $match->participants->where('pivot.team', 2))
                        <article class="flex items-center gap-3 px-3 py-3">
                            <span @class(['grid size-7 shrink-0 place-items-center rounded-md text-[9px] font-extrabold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index !== 0])>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="min-w-0 flex-1"><p class="truncate text-[10px] font-bold text-pine">{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</p><p class="mt-0.5 truncate text-[9px] text-muted">vs {{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</p></div>
                            @if($index === 0)<span class="shrink-0 rounded-full bg-pine-soft px-2 py-1 text-[8px] font-bold text-pine">Up next</span>@endif
                        </article>
                    @empty
                        <p class="px-4 py-6 text-center text-[10px] text-muted">No queued matchups yet.</p>
                    @endforelse
                </div>
                <div class="border-t border-line bg-[#fbfbf7] px-4 py-3"><div class="flex items-center justify-between gap-2"><h4 class="text-[10px] font-bold text-pine">Bench</h4><span class="ui-badge px-2 py-0.5 text-[9px]">{{ $benchPlayers->count() }}</span></div><div class="mt-2 flex flex-wrap gap-1.5">@forelse($benchPlayers as $player)<span class="rounded-full border border-line bg-white px-2.5 py-1 text-[9px] font-semibold text-pine">{{ $player->displayName() }}</span>@empty<p class="text-[9px] text-muted">No players waiting on the bench.</p>@endforelse</div></div>
            </section>
            <section class="rounded-xl border border-court-line bg-pine-soft/70 p-4"><h3 class="text-xs font-bold text-pine"><i class="bi bi-info-circle mr-1" aria-hidden="true"></i>How the queue works</h3><p class="mt-1.5 text-[10px] leading-5 text-muted">The first eligible matchup starts on the next available court. Players who are checked in but do not yet have a matchup stay on the bench.</p></section>
        </div>
    </div>
@elseif($tab === 'ranks')
    <div class="mb-4"><h2 class="text-lg font-extrabold text-pine">Ranks & history</h2><p class="mt-1 text-xs text-muted">Standings beside the latest completed matches.</p></div>
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)]">
        <section class="min-w-0 overflow-hidden rounded-xl border border-line bg-surface shadow-card">
            <div class="border-b border-line px-4 py-3.5"><h3 class="text-sm font-extrabold text-pine">Standings <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $standings->count() }}</span></h3><p class="mt-0.5 text-[10px] text-muted">Wins first, then point differential · choose a player to filter history</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-left text-xs">
                    <thead class="bg-[#f3f5ed] text-[9px] font-bold text-muted"><tr><th scope="col" class="px-3 py-2.5">Rank</th><th scope="col" class="px-3 py-2.5">Player</th><th scope="col" class="px-3 py-2.5 text-center">W–L</th><th scope="col" class="px-3 py-2.5 text-right">Diff.</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse($standings as $index => $row)
                            <tr class="hover:bg-[#fbfcf8]">
                                <td class="px-3 py-2.5"><span @class(['grid size-6 place-items-center rounded-md text-[9px] font-bold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index > 0])>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                                <th scope="row" class="px-3 py-2.5"><a class="flex items-center gap-2 font-semibold text-pine hover:text-pine-mid" href="{{ route('events.show', [$session, 'tab' => 'ranks', 'player' => $row['player']->id]) }}"><span class="grid size-7 place-items-center rounded-full bg-pine-soft text-[9px] font-bold">{{ strtoupper(substr($row['player']->displayName(), 0, 1)) }}</span>{{ $row['player']->displayName() }}</a></th>
                                <td class="px-3 py-2.5 text-center font-medium text-pine">{{ $row['wins'] }}–{{ $row['losses'] }}</td><td class="px-3 py-2.5 text-right font-semibold text-pine">{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-[10px] text-muted">Scores will establish the standings.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="min-w-0 overflow-hidden rounded-xl border border-line bg-surface shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3.5">
                <div><h3 class="text-sm font-extrabold text-pine">Match history <span class="ml-1 rounded-md bg-pine-soft px-1.5 py-1 text-[10px]">{{ $history->total() }}</span></h3><p class="mt-0.5 text-[10px] text-muted">{{ $selectedPlayer ? 'Filtered to '.$playerStats[$selectedPlayer]['player']->displayName() : 'Newest results first' }}</p></div>
                @if($selectedPlayer)<a class="btn-outline min-h-8 px-2.5 text-[9px]" href="{{ route('events.show', [$session, 'tab' => 'ranks']) }}">Clear filter</a>@endif
            </div>
            <div class="divide-y divide-line">
                @forelse($history as $match)
                    @php($teamOne = $match->participants->where('pivot.team', 1))
                    @php($teamTwo = $match->participants->where('pivot.team', 2))
                    <article class="flex items-center gap-2.5 px-3 py-3 sm:px-4">
                        <div class="w-16 shrink-0"><strong class="block truncate text-[9px] text-pine">{{ $match->court?->name ?? 'Match' }}</strong><span class="mt-0.5 block text-[8px] text-muted">{{ $match->completed_at?->format('g:i A') }}</span></div>
                        <div class="min-w-0 flex-1">
                            <p @class(['truncate text-[10px] font-semibold', 'text-pine' => $match->team_one_score > $match->team_two_score, 'text-muted' => $match->team_one_score < $match->team_two_score])>{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</p>
                            <p @class(['mt-1 truncate text-[9px]', 'text-pine' => $match->team_two_score > $match->team_one_score, 'text-muted' => $match->team_two_score < $match->team_one_score])">vs {{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</p>
                        </div>
                        <span class="shrink-0 rounded-md bg-pine-soft px-2 py-1.5 text-[10px] font-extrabold text-pine">{{ $match->team_one_score }}–{{ $match->team_two_score }}</span>
                    </article>
                    @if($canOrganize)
                        <details class="mx-3 mb-3 rounded-lg border border-line px-3 py-2 sm:mx-4">
                            <summary class="cursor-pointer text-[9px] font-semibold text-muted">Correct score</summary>
                            <form method="POST" action="{{ route('events.matches.correct', [$session, $match]) }}" class="mt-2 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-end gap-2">@csrf @method('PUT')
                                <div><label class="field-label mb-1 text-[9px]" for="correct-one-{{ $match->id }}">Team 1</label><input class="field min-h-9 px-2 text-xs" id="correct-one-{{ $match->id }}" type="number" name="team_one_score" min="0" max="99" value="{{ $match->team_one_score }}" required></div><span class="pb-2 text-muted">–</span>
                                <div><label class="field-label mb-1 text-[9px]" for="correct-two-{{ $match->id }}">Team 2</label><input class="field min-h-9 px-2 text-xs" id="correct-two-{{ $match->id }}" type="number" name="team_two_score" min="0" max="99" value="{{ $match->team_two_score }}" required></div><button class="btn-secondary min-h-9 px-2.5 text-[9px]">Save</button>
                            </form>
                        </details>
                    @endif
                @empty
                    <div class="px-4 py-9 text-center"><i class="bi bi-clock-history text-xl text-pine-mid" aria-hidden="true"></i><p class="mt-2 text-xs font-semibold text-pine">No completed matches</p><p class="mt-1 text-[10px] text-muted">Results will appear here after games are scored.</p></div>
                @endforelse
            </div>
            <div class="border-t border-line px-4 py-3">{{ $history->links() }}</div>
        </section>
    </div>
    @if($canOrganize && !$session->finalized_at)
        <form method="POST" action="{{ route('events.finalize', $session) }}" class="mt-4 flex flex-col items-center gap-2 rounded-xl border border-line bg-surface p-4 sm:flex-row sm:justify-between">@csrf
            <p class="text-[10px] text-muted">Finalizing locks all scores and the final standings.</p><button class="btn-outline min-h-9" {{ $session->matches->whereIn('status', ['playing', 'queued'])->isNotEmpty() ? 'disabled' : '' }}><i class="bi bi-lock" aria-hidden="true"></i>Finalize event</button>
        </form>
    @endif
@endif
@endsection
