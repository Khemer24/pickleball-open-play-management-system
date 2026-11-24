@extends('layouts.app')
@section('title', 'Open Plays')
@section('content')
@php($isPlayer = auth()->user()->role === 'player')
<header class="relative isolate mb-6 overflow-hidden rounded-2xl bg-pine-deep px-5 py-6 text-white sm:px-7 sm:py-7">
    <div class="absolute inset-0 -z-10 opacity-20" aria-hidden="true"><div class="absolute inset-0 bg-[radial-gradient(#d8ed9f_1px,transparent_1px)] [background-size:14px_14px]"></div></div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-[.15em] text-lime">RallyCourt · Open Plays</div>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Open Plays</h1>
            <p class="mt-1 text-sm text-white/70">{{ $isPlayer ? 'Open plays you’ve played or have been added to.' : 'Find a session, follow your games, or get the next one started.' }}</p>
        </div>
        @if(auth()->user()->isManager())
            <a href="{{ route('open-plays.create') }}" class="btn-primary shrink-0"><i class="bi bi-plus-lg" aria-hidden="true"></i>Create session</a>
        @elseif(auth()->user()->role === 'manager')
            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white">Manager approval pending</span>
        @endif
    </div>
</header>

<form method="GET" action="{{ route('open-plays.index') }}" class="mb-6 flex max-w-2xl flex-col gap-2 sm:flex-row">
    <label class="relative min-w-0 flex-1">
        <span class="sr-only">Search open plays by name or creator</span>
        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted" aria-hidden="true"></i>
        <input class="field min-h-11 pl-9" type="search" name="search" value="{{ $search }}" placeholder="Search by name or creator">
    </label>
    <button class="btn-secondary min-h-11 justify-center px-5" type="submit">Search</button>
    @if($search)<a class="btn-outline min-h-11 justify-center" href="{{ route('open-plays.index') }}">Clear</a>@endif
</form>

@if($mySessions->isNotEmpty())
    <section class="mb-7">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-pine">Your sessions</h2>
                <p class="mt-1 text-xs text-muted">{{ $isPlayer ? 'Sessions your manager has added you to.' : 'Open plays you host or have been added to.' }}</p>
            </div>
            <span class="ui-badge">{{ $mySessions->count() }} {{ $mySessions->count() === 1 ? 'session' : 'sessions' }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($mySessions as $session)
                @php($isMine = $session->manager_id === auth()->id())
                @php($isAssigned = $session->assignedManagers->contains('id', auth()->id()))
                <article class="ui-card flex h-full flex-col p-5 transition hover:-translate-y-0.5 hover:border-court-line hover:shadow-lg">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ui-badge">{{ ucfirst($session->format) === 'Doubles' ? '2v2' : '1v1' }}</span>
                        <span class="ui-badge">{{ ucfirst($session->skill_level) }}</span>
                        @if($session->finalized_at)<span class="rounded-full bg-lime px-2.5 py-1 text-[10px] font-bold text-pine-deep">Finalized</span>@elseif($session->active_matches_count)<span class="live-pill"><i class="bi bi-circle-fill text-[8px]" aria-hidden="true"></i>Live now</span>@endif
                    </div>
                    <h3 class="mt-4 text-base font-bold leading-snug text-pine">{{ $session->name }}</h3>
                    <p class="mt-1 text-xs text-muted">Created by {{ $session->manager->name }}</p>
                    <div class="my-4 flex flex-wrap gap-x-3 gap-y-1.5 text-[11px] text-muted">
                        <span>{{ $session->players->count() }} {{ $session->players->count() === 1 ? 'player' : 'players' }}</span><span aria-hidden="true">·</span>
                        <span>{{ $session->courts->count() }} {{ $session->courts->count() === 1 ? 'court' : 'courts' }}</span><span aria-hidden="true">·</span>
                        <span>{{ $session->completed_matches_count }} {{ $session->completed_matches_count === 1 ? 'match' : 'matches' }}</span>
                    </div>
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-3">
                        <span class="text-[10px] text-muted">{{ $session->play_date->format('M j, Y') }}</span>
                        @if($isPlayer)
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('open-plays.show', $session) }}">View open play <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @elseif($session->organizer_id === auth()->id() && auth()->user()->isOrganizer())
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('events.show', $session) }}">Open event</a>
                        @elseif($isAssigned && $session->organizer_id)
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('events.show', $session) }}">Open event</a>
                        @elseif($isMine && auth()->user()->isManager() && !$session->finalized_at)
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('open-plays.manage', $session) }}">Manage session</a>
                        @elseif($session->finalized_at)
                            <span class="text-[10px] font-semibold text-muted">Final standings locked</span>
                        @else
                            <span class="badge-success">You’re in</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if($sessions->isNotEmpty())
    <section>
        <div class="mb-3">
            <h2 class="text-lg font-bold text-pine">{{ $isPlayer ? 'Upcoming community sessions' : 'Community sessions' }}</h2>
            <p class="mt-1 text-xs text-muted">Other upcoming open plays in your community.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($sessions as $session)
                @php($isMine = $session->manager_id === auth()->id())
                @php($isAssigned = $session->assignedManagers->contains('id', auth()->id()))
                <article class="ui-card flex h-full flex-col p-5 transition hover:-translate-y-0.5 hover:border-court-line hover:shadow-lg">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ui-badge">{{ ucfirst($session->format) === 'Doubles' ? '2v2' : '1v1' }}</span>
                        <span class="ui-badge">{{ ucfirst($session->skill_level) }}</span>
                        @if($session->active_matches_count)<span class="live-pill"><i class="bi bi-circle-fill text-[8px]" aria-hidden="true"></i>Live now</span>@endif
                    </div>
                    <h3 class="mt-4 text-base font-bold leading-snug text-pine">{{ $session->name }}</h3>
                    <p class="mt-1 text-xs text-muted">Created by {{ $session->manager->name }}</p>
                    <div class="my-4 flex flex-wrap gap-x-3 gap-y-1.5 text-[11px] text-muted">
                        <span>{{ $session->players->count() }} {{ $session->players->count() === 1 ? 'player' : 'players' }}</span><span aria-hidden="true">·</span>
                        <span>{{ $session->courts->count() }} {{ $session->courts->count() === 1 ? 'court' : 'courts' }}</span><span aria-hidden="true">·</span>
                        <span>{{ $session->completed_matches_count }} {{ $session->completed_matches_count === 1 ? 'match' : 'matches' }}</span>
                    </div>
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-3">
                        <span class="text-[10px] text-muted">{{ $session->play_date->format('M j, Y') }}</span>
                        @if($session->organizer_id === auth()->id() && auth()->user()->isOrganizer())
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('events.show', $session) }}">Open event</a>
                        @elseif($isAssigned && $session->organizer_id)
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('events.show', $session) }}">Open event</a>
                        @elseif($isMine && auth()->user()->isManager())
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('open-plays.manage', $session) }}">Manage session</a>
                        @elseif(auth()->user()->sessionEntries()->where('open_play_session_id', $session->id)->exists())
                            <a class="btn-outline min-h-8 px-3 text-[10px]" href="{{ route('open-plays.show', $session) }}">View open play</a>
                        @else
                            <span class="text-[10px] text-muted">Ask the host to add you</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if($mySessions->isEmpty() && $sessions->isEmpty())
    <div class="ui-card px-5 py-12 text-center">
        <div class="empty-state-icon mb-2"><i class="bi bi-calendar2-x" aria-hidden="true"></i></div>
        <h2 class="text-lg font-bold text-pine">{{ $search ? 'No matching open plays' : 'No open plays listed yet' }}</h2>
        <p class="mt-2 text-sm text-muted">{{ $search ? 'Try another session or creator name.' : 'Check back soon for a new session.' }}</p>
    </div>
@endif
@endsection
