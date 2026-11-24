@extends('layouts.app')
@section('title', 'My Events')
@section('content')
<header class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div><div class="eyebrow">Organizer workspace</div><h1>My Events</h1><p class="subheading">Plan open plays, keep courts moving, and wrap up results.</p></div>
    @if(auth()->user()->isOrganizer())<a href="{{ route('events.create') }}" class="btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Create event</a>@endif
</header>
@if(!auth()->user()->isOrganizer())
    <div class="status-message status-info mb-5" role="status">Your Organizer application is waiting for admin approval. You can browse your events once approved.</div>
@endif
@foreach([['Upcoming', $upcoming, 'bi-calendar-event'], ['Live', $live, 'bi-broadcast'], ['Completed', $completed, 'bi-check2-circle']] as [$title, $events, $icon])
    <section class="mb-7">
        <div class="mb-3 flex items-end justify-between"><div><h2 class="text-xl font-bold text-pine">{{ $title }}</h2><p class="mt-1 text-xs text-muted">{{ $events->count() }} {{ \Illuminate\Support\Str::plural('event', $events->count()) }}</p></div></div>
        @if($events->isEmpty())
            <div class="rounded-2xl border border-dashed border-line bg-white/40 px-5 py-7 text-center text-sm text-muted">No {{ strtolower($title) }} events.</div>
        @else
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($events as $event)
                    <a href="{{ route('events.show', $event) }}" class="ui-card group p-5 hover:border-court-line hover:bg-pine-soft/40">
                        <div class="flex items-start justify-between gap-3"><span class="ui-badge"><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</span><span class="text-xs font-semibold text-muted">{{ ucfirst($event->skill_level) }}</span></div>
                        <h3 class="mt-4 text-lg font-bold text-pine">{{ $event->name }}</h3>
                        <p class="mt-1 text-xs text-muted">{{ $event->venue }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-muted">
                            <span><i class="bi bi-calendar3 mr-1.5" aria-hidden="true"></i>{{ $event->play_date->format('D, M j') }}@if($event->starts_at) · {{ $event->starts_at->format('g:i A') }}@endif</span>
                            <span><i class="bi bi-grid-3x3 mr-1.5" aria-hidden="true"></i>{{ ucfirst($event->format) }} · {{ $event->courts->count() }} courts</span>
                            <span><i class="bi bi-people mr-1.5" aria-hidden="true"></i>{{ $event->players_count }}{{ $event->player_limit ? '/'.$event->player_limit : '' }} players</span>
                            @if($event->active_matches_count)<span class="live-pill"><i class="bi bi-circle-fill text-[8px]" aria-hidden="true"></i>{{ $event->active_matches_count }} courts live</span>@endif
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-line pt-3 text-xs"><span class="text-muted">Organized by {{ $event->organizer?->name ?? auth()->user()->name }}</span><span class="font-bold text-pine group-hover:text-pine-mid">Open event <i class="bi bi-arrow-right" aria-hidden="true"></i></span></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endforeach
@endsection
