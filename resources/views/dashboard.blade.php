@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<header class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
    <div><div class="eyebrow">Your court-side summary</div><h1>Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}, {{ explode(' ', auth()->user()->name)[0] }} <i class="bi bi-stars ml-1 text-base text-pine-mid" aria-hidden="true"></i></h1><p class="subheading">Here’s how your game is looking.</p></div>
    @if(auth()->user()->isOrganizer())<a href="{{ route('events.create') }}" class="btn-primary w-full shrink-0 sm:w-auto"><i class="bi bi-plus-lg" aria-hidden="true"></i> New event</a>
    @elseif(auth()->user()->isManager())<a href="{{ route('open-plays.create') }}" class="btn-primary w-full shrink-0 sm:w-auto"><i class="bi bi-plus-lg" aria-hidden="true"></i> New open play</a>@endif
</header>
@if((auth()->user()->role === 'manager' && !auth()->user()->manager_approved) || (auth()->user()->role === 'organizer' && !auth()->user()->organizer_approved))
    <div class="status-message status-info mb-5" role="status">Your {{ auth()->user()->role }} application is under review. You’ll be able to create and run events once an admin approves your account.</div>
@endif
<section class="mb-5 grid gap-3 md:grid-cols-3">
    <article class="ui-card min-h-28 p-4 sm:p-5"><div class="flex items-center justify-between"><span class="text-xs font-semibold text-muted">Win rate</span><span class="grid size-9 place-items-center rounded-xl bg-pine-soft text-pine"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span></div><div class="mt-2 text-4xl font-extrabold tracking-tight text-pine">{{ $winRate }}%</div><div class="mt-1 text-xs text-muted">{{ $wins }} wins <span class="mx-1">·</span> {{ $losses }} losses</div></article>
    <article class="ui-card min-h-28 p-4 sm:p-5"><div class="flex items-center justify-between"><span class="text-xs font-semibold text-muted">Open plays played</span><span class="grid size-9 place-items-center rounded-xl bg-[#eff4df] text-pine"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span></div><div class="mt-2 text-4xl font-extrabold tracking-tight text-pine">{{ $openPlays }}</div><div class="mt-1 text-xs text-muted">Distinct sessions with a completed match</div></article>
    <article class="ui-card min-h-28 p-4 sm:p-5"><div class="flex items-center justify-between"><span class="text-xs font-semibold text-muted">Matches this week</span><span class="grid size-9 place-items-center rounded-xl bg-pine-soft text-pine"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span></div><div class="mt-2 text-4xl font-extrabold tracking-tight text-pine">{{ $weeklyTotal }}</div><div class="mt-1 text-xs text-muted">Completed over the last seven days</div></article>
</section>
<section class="relative isolate mb-5 flex flex-col items-start justify-between gap-5 overflow-hidden rounded-2xl border border-[#a9d894] bg-[#bce99e] px-5 py-5 sm:flex-row sm:items-center sm:px-6">
    <svg class="pointer-events-none absolute -right-12 -top-8 hidden h-40 w-72 rotate-[-12deg] text-pine/70 sm:block" viewBox="0 0 360 200" fill="none" aria-hidden="true"><path d="M45 34 306 23l24 142-264 12L45 34Z" stroke="currentColor" stroke-width="8" stroke-linejoin="round"/><path d="m66 49 222-9 17 111-224 10L66 49Zm113-5 8 111M66 103l236-10M113 47l10 117m128-122 10 117" stroke="currentColor" stroke-width="2"/></svg>
    <div class="relative z-10"><div class="mb-1 inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-[.14em] text-pine"><i class="bi bi-circle-fill text-[6px]" aria-hidden="true"></i> Back on court</div><h2 class="mb-1 text-lg font-extrabold text-pine">Find your next open play</h2><p class="text-sm text-[#42584a]">Meet new players, find your rhythm, and get back on court.</p></div>
    <a href="{{ route('open-plays.index') }}" class="btn-primary relative z-10 w-full shrink-0 sm:w-auto">View open plays <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</section>
@if($nextSessions->isNotEmpty())
<section class="ui-card panel-card mb-4">
    <div class="card-heading"><div><h2>Your upcoming sessions</h2><p>Sessions you’ve been added to</p></div></div>
    <div class="flex flex-wrap gap-2">
        @foreach($nextSessions as $session)
            <a class="rounded-xl border border-line bg-paper px-4 py-3 transition-colors hover:border-court-line hover:bg-pine-soft" href="{{ route('open-plays.index') }}"><strong class="block text-sm font-bold text-pine">{{ $session->name }}</strong><small class="mt-1 block text-xs text-muted">{{ $session->play_date->format('D, M j') }} · {{ $session->venue }}</small></a>
        @endforeach
    </div>
</section>
@endif
<div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,.85fr)]">
    <div>
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Matches this week</h2><p>Your last seven days on court</p></div><span class="ui-badge">{{ $weeklyTotal }} total</span></div>
            <div class="flex h-[140px] items-end gap-2.5 pt-2.5" role="img" aria-label="Matches per day for the last seven days">
                @foreach($daily as $day)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2 text-[10px] {{ $day['today'] ? 'font-bold text-pine' : 'text-muted' }}" title="{{ $day['count'] }} matches">
                        <span>{{ $day['count'] ?: '' }}</span><div class="flex h-[106px] w-full items-end justify-center"><div class="min-h-1.5 w-7 rounded-t-lg {{ $day['today'] ? 'bg-lime' : 'bg-court-line' }}" style="height:{{ $day['count'] ? min(100, 18 + $day['count'] * 25) : 5 }}%"></div></div><span>{{ $day['today'] ? 'Today' : $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Recent matches</h2><p>Your five latest results</p></div></div>
            @forelse($recentMatches as $match)
                @php($mine = $match->participants->firstWhere('user_id', auth()->id()))
                @php($won = $mine && (($mine->pivot->team === 1 && $match->team_one_score > $match->team_two_score) || ($mine->pivot->team === 2 && $match->team_two_score > $match->team_one_score)))
                @php($teamOne = $match->participants->where('pivot.team', 1))
                @php($teamTwo = $match->participants->where('pivot.team', 2))
                <div class="flex items-center gap-3 border-b border-line py-3.5 last:border-0 last:pb-0">
                    <div class="w-11 shrink-0 rounded-xl bg-paper px-1 py-1.5 text-center text-[10px] font-semibold text-muted"><strong class="block text-base text-pine">{{ $match->completed_at->format('d') }}</strong>{{ $match->completed_at->format('M') }}</div>
                    <div class="min-w-0 flex-1">
                        <strong class="block text-[13px] text-pine">{{ $match->session->name }} <span class="result-badge {{ $won ? 'result-win' : 'result-loss' }}">{{ $won ? 'W' : 'L' }}</span></strong>
                        <div class="mt-0.5 text-[11px] leading-5 text-muted">
                            <span class="{{ $match->team_one_score > $match->team_two_score ? 'font-bold text-pine-mid' : '' }}">{{ $teamOne->map(fn($p) => $p->displayName())->join(' & ') }}</span>
                            <span class="mx-1">vs</span>
                            <span class="{{ $match->team_two_score > $match->team_one_score ? 'font-bold text-pine-mid' : '' }}">{{ $teamTwo->map(fn($p) => $p->displayName())->join(' & ') }}</span>
                            <span class="block text-[10px]">{{ $match->started_at->format('g:i A') }} · {{ $match->started_at->diffInMinutes($match->completed_at) }} min · {{ $match->court->name }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 text-right text-sm font-extrabold text-pine">{{ $match->team_one_score }}–{{ $match->team_two_score }}<small class="block text-[10px] font-normal text-muted">final</small></div>
                </div>
            @empty
                <div class="py-6 text-center text-muted"><div class="empty-state-icon mb-2"><i class="bi bi-trophy" aria-hidden="true"></i></div><div class="font-semibold text-ink">Your next match starts here</div><small>Completed matches will show up in this list.</small></div>
            @endforelse
        </section>
    </div>
    <div>
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Best teammates</h2><p>Partners with your strongest record</p></div></div>
            @forelse($bestTeammates as $record)
                <div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 last:pb-0">
                    @if($record['avatar_path'])<img class="avatar" src="{{ Storage::url($record['avatar_path']) }}" alt="">@else<span class="avatar">{{ strtoupper(substr($record['name'],0,1)) }}</span>@endif
                    <div class="min-w-0 flex-1"><strong class="block truncate text-xs text-pine">{{ $record['name'] }}</strong><span class="text-[10px] text-muted">{{ $record['wins'] }} wins · {{ $record['losses'] }} losses</span></div><div class="text-right text-sm font-bold text-pine">{{ $record['rate'] }}%<small class="block text-[10px] font-normal text-muted">{{ $record['games'] }} games</small></div>
                </div>
            @empty <p class="text-xs text-muted">Partner up in a few matches to see your best teammate stats.</p>@endforelse
        </section>
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Toughest opponents</h2><p>Players who challenge you most</p></div></div>
            @forelse($toughestOpponents as $record)
                <div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 last:pb-0">
                    @if($record['avatar_path'])<img class="avatar" src="{{ Storage::url($record['avatar_path']) }}" alt="">@else<span class="avatar">{{ strtoupper(substr($record['name'],0,1)) }}</span>@endif
                    <div class="min-w-0 flex-1"><strong class="block truncate text-xs text-pine">{{ $record['name'] }}</strong><span class="text-[10px] text-muted">{{ $record['wins'] }} wins · {{ $record['losses'] }} losses</span></div><div class="text-right text-sm font-bold text-pine">{{ $record['rate'] }}%<small class="block text-[10px] font-normal text-muted">{{ $record['games'] }} games</small></div>
                </div>
            @empty <p class="text-xs text-muted">Opponent stats will appear as you play matches.</p>@endforelse
        </section>
    </div>
</div>
@endsection
