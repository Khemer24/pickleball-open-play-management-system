@extends('layouts.app')
@section('title', $session->name)
@section('content')
@php($activeMatchCount = $session->matches->where('status', 'playing')->count())
@php($completedMatchCount = $session->matches->where('status', 'completed')->count())
<header class="mb-5 overflow-hidden rounded-2xl border border-[#d6e5ca] bg-[#e6f0dd]">
    <div class="relative isolate overflow-hidden px-5 py-5 sm:px-7 sm:py-6">
        <div class="absolute inset-0 -z-10 opacity-30" aria-hidden="true"><div class="absolute inset-0 bg-[radial-gradient(#6f9c68_1px,transparent_1px)] [background-size:14px_14px]"></div></div>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if($session->finalized_at)
                        <span class="rounded-full bg-lime px-2.5 py-1 text-[10px] font-bold text-pine-deep">Finalized</span>
                    @elseif($activeMatchCount)
                        <span class="live-pill"><i class="bi bi-circle-fill text-[8px]" aria-hidden="true"></i>Live now</span>
                    @else
                        <span class="ui-badge">Open play</span>
                    @endif
                    <span class="ui-badge">{{ $session->format === 'doubles' ? '2v2' : '1v1' }}</span>
                    <span class="ui-badge">{{ ucfirst($session->skill_level) }}</span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-extrabold tracking-tight text-pine-deep sm:text-3xl">{{ $session->name }}</h1>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-pine">
                    <span><i class="bi bi-geo-alt mr-1.5" aria-hidden="true"></i>{{ $session->venue }}</span>
                    <span><i class="bi bi-calendar3 mr-1.5" aria-hidden="true"></i>{{ $session->starts_at?->format('M j, Y · g:i A') ?? $session->play_date->format('M j, Y') }}</span>
                    <span><i class="bi bi-person-badge mr-1.5" aria-hidden="true"></i>Created by {{ $session->manager->name }}</span>
                </div>
            </div>
            @if($session->rules)
                <details class="max-w-sm rounded-xl border border-pine/15 bg-white/70 px-3 py-2">
                    <summary class="cursor-pointer text-xs font-bold text-pine"><i class="bi bi-card-text mr-1.5" aria-hidden="true"></i>Rules</summary>
                    <p class="mt-2 whitespace-pre-line text-xs leading-5 text-muted">{{ $session->rules }}</p>
                </details>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-3 border-t border-[#d6e5ca] bg-white/70">
        <div class="px-3 py-3 text-center"><div class="text-[9px] font-bold uppercase tracking-wider text-muted">Players</div><div class="mt-1 text-base font-extrabold text-pine">{{ $session->players->count() }}</div></div>
        <div class="border-x border-[#d6e5ca] px-3 py-3 text-center"><div class="text-[9px] font-bold uppercase tracking-wider text-muted">Courts live</div><div class="mt-1 text-base font-extrabold text-pine">{{ $activeMatchCount }}<span class="font-medium text-muted"> / {{ $session->courts->count() }}</span></div></div>
        <div class="px-3 py-3 text-center"><div class="text-[9px] font-bold uppercase tracking-wider text-muted">Matches</div><div class="mt-1 text-base font-extrabold text-pine">{{ $completedMatchCount }}</div></div>
    </div>
</header>

<nav aria-label="Open play sections" class="mb-5 grid grid-cols-3 gap-1 rounded-xl border border-line bg-[#eff1e7] p-1">
    @foreach(['players' => ['Players', 'bi-people'], 'courts' => ['Courts', 'bi-grid-3x3'], 'ranks' => ['Ranks & History', 'bi-trophy']] as $key => [$label, $icon])
        <a href="{{ route('open-plays.show', [$session, 'tab' => $key]) }}" @class([
            'flex min-h-10 items-center justify-center gap-1.5 rounded-lg px-2 text-center text-[10px] font-bold transition-colors sm:text-xs',
            'bg-lime text-pine-deep shadow-sm' => $tab === $key,
            'text-muted hover:bg-white/70 hover:text-pine' => $tab !== $key,
        ]) @if($tab === $key) aria-current="page" @endif>
            <i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
            @if($key === 'players')<span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[9px]">{{ $session->players->count() }}</span>@endif
            @if($key === 'ranks')<span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[9px]">{{ $completedMatchCount }}</span>@endif
        </a>
    @endforeach
</nav>

@if($tab === 'players')
    <section class="ui-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div><h2 class="text-sm font-extrabold text-pine">Players</h2><p class="mt-1 text-[10px] text-muted">Check who’s on court, up next, or waiting.</p></div>
            <label class="relative w-full sm:max-w-xs">
                <span class="sr-only">Search players</span><i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                <input class="field min-h-9 pl-9 text-xs" type="search" placeholder="Search name" data-roster-search>
            </label>
        </div>
        <div class="grid gap-4 p-4 lg:grid-cols-2 sm:p-5">
            @foreach([
                ['title' => 'Checked in players', 'players' => $session->players->whereNotNull('checked_in_at')->sortBy('checked_in_at')->values()],
                ['title' => 'Remaining roster', 'players' => $session->players->whereNull('checked_in_at')->sortBy(fn ($player) => mb_strtolower($player->displayName()))->values()],
            ] as $group)
                <section class="overflow-hidden rounded-xl border border-line">
                    <div class="flex items-center justify-between gap-3 border-b border-line bg-[#f7f8f2] px-3 py-3">
                        <h3 class="text-xs font-bold text-pine">{{ $group['title'] }}</h3>
                        <span class="ui-badge px-2 py-0.5 text-[9px]">{{ $group['players']->count() }}</span>
                    </div>
                    <div class="divide-y divide-line">
                        @forelse($group['players'] as $player)
                            @php($stats = $playerStats[$player->id])
                            @php($status = $activePlayerIds->contains($player->id) ? 'On court' : ($queuedPlayerIds->contains($player->id) ? 'Up next' : ($player->checked_in_at ? 'On bench' : 'Not checked in')))
                            <article class="flex items-center gap-2.5 px-3 py-3" data-roster-row data-player-name="{{ mb_strtolower($player->displayName()) }}">
                                @if($player->user?->avatar_path)
                                    <img class="size-9 shrink-0 rounded-full object-cover" src="{{ Storage::url($player->user->avatar_path) }}" alt="">
                                @else
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-pine-soft text-[10px] font-bold text-pine">{{ strtoupper(mb_substr($player->displayName(), 0, 1)) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-semibold text-pine">{{ $player->displayName() }} @if($player->user_id === auth()->id())<span class="ml-1 text-[9px] font-medium text-muted">You</span>@endif</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        @if($status === 'On court')<span class="live-pill px-2 py-0.5 text-[9px]">On court</span>
                                        @elseif($status === 'Up next')<span class="rounded-full bg-[#edf4d8] px-2 py-0.5 text-[9px] font-semibold text-pine">Up next</span>
                                        @elseif($status === 'On bench')<span class="ui-badge px-2 py-0.5 text-[9px]">On bench</span>
                                        @else<span class="text-[9px] text-muted">Not checked in</span>@endif
                                        <span class="{{ $player->fee_paid ? 'badge-success' : 'badge-warning' }} px-2 py-0.5 text-[9px]">{{ $player->fee_paid ? 'Paid' : 'Unpaid' }}</span>
                                    </div>
                                </div>
                                <span class="shrink-0 text-[10px] font-semibold text-muted">{{ $stats['wins'] }}W–{{ $stats['losses'] }}L</span>
                            </article>
                        @empty
                            <p class="px-3 py-8 text-center text-[10px] text-muted">No players in this list.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>
@elseif($tab === 'courts')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-lg font-extrabold text-pine">Courts</h2><p class="mt-1 text-xs text-muted">Live games, queued matches, and players on the bench.</p></div>
        <span class="ui-badge"><span class="size-1.5 rounded-full bg-[#3a8955]" aria-hidden="true"></span>{{ $activeMatchCount }} courts live</span>
    </div>
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,.8fr)]">
        <section class="ui-card">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5"><div><h3 class="text-sm font-extrabold text-pine">Live courts</h3><p class="mt-1 text-[10px] text-muted">Match lineups and timers</p></div><span class="ui-badge">{{ $activeMatchCount }} active</span></div>
            @if($session->courts->isEmpty())<p class="px-4 py-10 text-center text-xs text-muted">No courts have been added to this open play.</p>@endif
            <div class="grid gap-3 p-3 sm:grid-cols-2 sm:p-4">
                @foreach($session->courts as $court)
                    @php($match = $court->matches->first())
                    <article class="overflow-hidden rounded-xl border border-[#dce7d8] bg-white">
                        <div class="flex items-center justify-between bg-[#eef4e8] px-3 py-2.5"><span class="text-xs font-bold text-pine"><i class="bi bi-grid-3x3 mr-1.5" aria-hidden="true"></i>{{ $court->name }}</span>@if($match)<span class="live-pill">Live</span>@else<span class="ui-badge px-2 py-0.5 text-[9px]">Available</span>@endif</div>
                        @if($match)
                            @foreach([1, 2] as $team)
                                <div class="flex items-center justify-between gap-2 border-b border-line px-3 py-3 last:border-0">
                                    <div class="min-w-0"><span class="block text-[9px] font-bold uppercase tracking-wide text-muted">Team {{ $team }}</span><span class="mt-1 block truncate text-xs font-semibold text-pine">{{ $match->participants->where('pivot.team', $team)->map(fn ($player) => $player->displayName())->join(' & ') }}</span></div>
                                    <span class="grid size-7 place-items-center rounded-lg bg-pine-soft text-[10px] font-bold text-pine">{{ $team }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between border-t border-line px-3 py-2 text-[10px] text-muted"><span>Started {{ $match->started_at?->format('g:i A') }}</span><span class="rounded bg-pine-soft px-2 py-1 font-mono font-bold text-pine" data-live-timer="{{ $match->started_at?->timestamp }}" aria-label="Match elapsed time">00:00</span></div>
                        @else
                            <p class="px-3 py-7 text-center text-[10px] text-muted">Ready for the next match.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
        <div class="space-y-4">
            <section class="ui-card">
                <div class="flex items-center justify-between border-b border-line px-4 py-3.5"><div><h3 class="text-sm font-extrabold text-pine">Match queue</h3><p class="mt-1 text-[10px] text-muted">Waiting for the next available court.</p></div><span class="ui-badge">{{ $queuedMatches->count() }}</span></div>
                <div class="divide-y divide-line">
                    @forelse($queuedMatches as $index => $match)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span @class(['grid size-7 shrink-0 place-items-center rounded-lg text-[9px] font-extrabold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index > 0])>{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-1"><p class="truncate text-[10px] font-semibold text-pine">{{ $match->participants->where('pivot.team', 1)->map(fn ($player) => $player->displayName())->join(' & ') }}</p><p class="mt-1 truncate text-[9px] text-muted">vs {{ $match->participants->where('pivot.team', 2)->map(fn ($player) => $player->displayName())->join(' & ') }}</p></div>
                            @if($index === 0)<span class="rounded-full bg-pine-soft px-2 py-1 text-[9px] font-bold text-pine">Up next</span>@endif
                        </div>
                    @empty
                        <p class="px-4 py-7 text-center text-[10px] text-muted">No matchups are waiting.</p>
                    @endforelse
                </div>
                <div class="border-t border-line bg-[#fbfbf7] px-4 py-3"><div class="flex items-center justify-between"><h4 class="text-xs font-bold text-pine">On the bench</h4><span class="ui-badge">{{ $benchPlayers->count() }}</span></div><div class="mt-2 flex flex-wrap gap-1.5">@forelse($benchPlayers as $player)<span class="rounded-full border border-line bg-white px-2.5 py-1 text-[10px] font-semibold text-pine">{{ $player->displayName() }}</span>@empty<p class="text-[10px] text-muted">No players are waiting on the bench.</p>@endforelse</div></div>
            </section>
            @if($session->finalized_at)<p class="rounded-xl border border-[#dce7d8] bg-[#eff4df] px-4 py-3 text-xs text-pine"><i class="bi bi-lock mr-1" aria-hidden="true"></i>This open play is finalized. Courts and results are read-only.</p>@endif
        </div>
    </div>
@else
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)]">
        <section class="ui-card min-w-0">
            <div class="border-b border-line px-4 py-3.5"><h2 class="text-sm font-extrabold text-pine">Ranks</h2><p class="mt-1 text-[10px] text-muted">Standings ranked by wins, then point differential. Select a player to see their match history.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[380px] text-left text-xs">
                    <thead class="bg-[#f3f5ed] text-[9px] font-bold uppercase tracking-wide text-muted"><tr><th scope="col" class="px-3 py-2.5">Rank</th><th scope="col" class="px-3 py-2.5">Player</th><th scope="col" class="px-3 py-2.5 text-center">W–L</th><th scope="col" class="px-3 py-2.5 text-right">Diff.</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse($standings as $index => $row)
                            <tr class="hover:bg-[#fbfcf8]">
                                <td class="px-3 py-2.5"><span @class(['grid size-6 place-items-center rounded-md text-[9px] font-bold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index > 0])>{{ $index + 1 }}</span></td>
                                <th scope="row" class="px-3 py-2.5"><a class="flex items-center gap-2 font-semibold text-pine hover:text-pine-mid" href="{{ route('open-plays.show', [$session, 'tab' => 'ranks', 'player' => $row['player']->id]) }}">
                                    @if($row['player']->user?->avatar_path)<img class="size-7 rounded-full object-cover" src="{{ Storage::url($row['player']->user->avatar_path) }}" alt="">@else<span class="grid size-7 place-items-center rounded-full bg-pine-soft text-[9px] font-bold">{{ strtoupper(mb_substr($row['player']->displayName(), 0, 1)) }}</span>@endif
                                    <span class="truncate">{{ $row['player']->displayName() }}</span>
                                </a></th>
                                <td class="px-3 py-2.5 text-center font-semibold text-pine">{{ $row['wins'] }}W–{{ $row['losses'] }}L</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-pine">{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-[10px] text-muted">Scores will establish the standings.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="ui-card min-w-0">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5"><div><h2 class="text-sm font-extrabold text-pine">Match history</h2><p class="mt-1 text-[10px] text-muted">{{ $selectedPlayer ? 'Filtered to '.$playerStats[$selectedPlayer]['player']->displayName() : 'Completed matches, newest first.' }}</p></div>@if($selectedPlayer)<a class="btn-outline min-h-8 px-2.5 text-[9px]" href="{{ route('open-plays.show', [$session, 'tab' => 'ranks']) }}">Clear filter</a>@endif</div>
            <div class="divide-y divide-line">
                @forelse($history as $match)
                    @php($teamOne = $match->participants->where('pivot.team', 1))
                    @php($teamTwo = $match->participants->where('pivot.team', 2))
                    @php($duration = $match->started_at && $match->completed_at ? (int) round($match->started_at->diffInMinutes($match->completed_at)) : null)
                    <article class="px-3 py-3 sm:px-4">
                        <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-stretch gap-2">
                            <div @class(['relative flex min-w-0 flex-col items-center justify-center rounded-xl px-2 py-3 text-center', 'bg-[#eaf4c4]' => $match->team_one_score > $match->team_two_score, 'bg-[#edf1f6]' => $match->team_one_score < $match->team_two_score])>
                                @if($match->team_one_score > $match->team_two_score)<span class="mb-1 rounded-full bg-lime px-2 py-0.5 text-[8px] font-extrabold uppercase text-pine-deep"><i class="bi bi-trophy-fill mr-1" aria-hidden="true"></i>Winner</span>@endif
                                <span class="line-clamp-2 text-[9px] font-semibold text-pine">{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</span>
                                <strong class="mt-1 text-xl leading-none text-pine-deep">{{ $match->team_one_score }}</strong>
                            </div>
                            <span class="self-center text-[9px] font-bold text-muted">VS</span>
                            <div @class(['relative flex min-w-0 flex-col items-center justify-center rounded-xl px-2 py-3 text-center', 'bg-[#eaf4c4]' => $match->team_two_score > $match->team_one_score, 'bg-[#edf1f6]' => $match->team_two_score < $match->team_one_score])>
                                @if($match->team_two_score > $match->team_one_score)<span class="mb-1 rounded-full bg-lime px-2 py-0.5 text-[8px] font-extrabold uppercase text-pine-deep"><i class="bi bi-trophy-fill mr-1" aria-hidden="true"></i>Winner</span>@endif
                                <span class="line-clamp-2 text-[9px] font-semibold text-pine">{{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</span>
                                <strong class="mt-1 text-xl leading-none text-pine-deep">{{ $match->team_two_score }}</strong>
                            </div>
                        </div>
                        <p class="mt-1.5 text-center text-[9px] text-muted">Started {{ $match->started_at?->format('g:i A') ?? $match->completed_at?->format('g:i A') }} · {{ $duration !== null ? 'Played '.$duration.' min' : 'Duration unavailable' }} · {{ $match->court?->name ?? 'Court' }}</p>
                    </article>
                @empty
                    <div class="px-4 py-10 text-center"><i class="bi bi-clock-history text-xl text-pine-mid" aria-hidden="true"></i><p class="mt-2 text-xs font-semibold text-pine">No completed matches yet</p><p class="mt-1 text-[10px] text-muted">Results will appear after the first game is scored.</p></div>
                @endforelse
            </div>
            @if($history->hasPages())<div class="border-t border-line px-4 py-3">{{ $history->links() }}</div>@endif
        </section>
    </div>
    @if($selectedPlayer)
        @php($selected = $playerStats[$selectedPlayer])
        <dialog open data-player-history-dialog aria-labelledby="player-history-title" class="m-auto max-h-[88vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-pine-deep/60">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-line bg-surface/95 p-4 backdrop-blur sm:p-5">
                <div class="flex min-w-0 items-center gap-3">
                    @if($selected['player']->user?->avatar_path)<img class="size-10 rounded-full object-cover" src="{{ Storage::url($selected['player']->user->avatar_path) }}" alt="">@else<span class="grid size-10 shrink-0 place-items-center rounded-full bg-pine-soft text-sm font-bold text-pine">{{ strtoupper(mb_substr($selected['player']->displayName(), 0, 1)) }}</span>@endif
                    <div class="min-w-0"><h2 id="player-history-title" class="truncate text-base font-extrabold text-pine">{{ $selected['player']->displayName() }}</h2><p class="mt-1 text-[10px] text-muted"><strong class="text-pine">{{ $selected['wins'] }}W · {{ $selected['losses'] }}L</strong> — match history in this open play</p></div>
                </div>
                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-lg border border-line text-muted hover:bg-paper hover:text-pine" data-close-player-history aria-label="Close player match history"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="divide-y divide-line">
                @forelse($history as $match)
                    @php($teamOne = $match->participants->where('pivot.team', 1))
                    @php($teamTwo = $match->participants->where('pivot.team', 2))
                    @php($playerInTeamOne = $teamOne->contains('id', $selectedPlayer))
                    @php($won = $playerInTeamOne ? $match->team_one_score > $match->team_two_score : $match->team_two_score > $match->team_one_score)
                    @php($duration = $match->started_at && $match->completed_at ? (int) round($match->started_at->diffInMinutes($match->completed_at)) : null)
                    <article class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-3 px-3 py-3 sm:px-5">
                        <span @class(['grid size-8 place-items-center rounded-full text-[10px] font-extrabold', 'bg-[#dcfce7] text-[#16803b]' => $won, 'bg-[#fee2e2] text-[#b42318]' => !$won])>{{ $won ? 'W' : 'L' }}</span>
                        <div>
                            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
                                <div @class(['rounded-lg px-2 py-2 text-center', 'bg-[#eaf4c4]' => $match->team_one_score > $match->team_two_score, 'bg-[#edf1f6]' => $match->team_one_score < $match->team_two_score])>
                                    <span class="block line-clamp-2 text-[9px] font-semibold text-pine">{{ $teamOne->map(fn ($player) => $player->displayName())->join(' & ') }}</span><strong class="mt-0.5 block text-lg text-pine-deep">{{ $match->team_one_score }}</strong>
                                </div>
                                <span class="text-[8px] font-bold text-muted">VS</span>
                                <div @class(['rounded-lg px-2 py-2 text-center', 'bg-[#eaf4c4]' => $match->team_two_score > $match->team_one_score, 'bg-[#edf1f6]' => $match->team_two_score < $match->team_one_score])>
                                    <span class="block line-clamp-2 text-[9px] font-semibold text-pine">{{ $teamTwo->map(fn ($player) => $player->displayName())->join(' & ') }}</span><strong class="mt-0.5 block text-lg text-pine-deep">{{ $match->team_two_score }}</strong>
                                </div>
                            </div>
                            <p class="mt-1 text-center text-[8px] text-muted">Started {{ $match->started_at?->format('g:i A') ?? $match->completed_at?->format('g:i A') }} · {{ $duration !== null ? $duration.' min' : 'Duration unavailable' }}</p>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-xs text-muted">This player has no completed matches in this open play.</p>
                @endforelse
            </div>
            @if($history->hasPages())<div class="border-t border-line px-4 py-3">{{ $history->links() }}</div>@endif
        </dialog>
    @endif
@endif
@endsection
