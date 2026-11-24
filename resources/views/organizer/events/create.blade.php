@extends('layouts.app')
@section('title', 'Create Event')
@section('content')
<header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div><div class="eyebrow"><a href="{{ route('events.index') }}" class="hover:text-pine">My Events</a> / New event</div><h1>Create an open play</h1><p class="subheading">Set the format, invite your players, and choose when to open a court.</p></div>
    <a class="btn-secondary" href="{{ route('events.index') }}">Cancel</a>
</header>
<form method="POST" action="{{ route('events.store') }}">@csrf
    <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
        <section class="ui-card p-5 sm:p-6">
            <div class="card-heading"><div><h2>Event details</h2><p>Share the essentials with your players</p></div><i class="bi bi-calendar2-plus text-lg text-pine-mid" aria-hidden="true"></i></div>
            <div class="mb-4"><label class="field-label" for="event-name">Event name</label><input id="event-name" class="field" name="name" value="{{ old('name') }}" placeholder="Saturday morning open play" required></div>
            <div class="mb-4"><label class="field-label" for="venue">Venue</label><input id="venue" class="field" name="venue" value="{{ old('venue') }}" placeholder="Riverside Pickleball Club" required></div>
            <div class="mb-4 grid gap-3 sm:grid-cols-3">
                <div><label class="field-label" for="play-date">Date</label><input id="play-date" class="field" type="date" name="play_date" value="{{ old('play_date', today()->toDateString()) }}" required></div>
                <div><label class="field-label" for="start-time">Start time</label><input id="start-time" class="field" type="time" name="start_time" value="{{ old('start_time') }}"></div>
                <div><label class="field-label" for="skill-level">Skill level</label><select id="skill-level" class="field" name="skill_level">@foreach(['beginner','intermediate','advanced','all levels'] as $level)<option value="{{ $level }}" @selected(old('skill_level','all levels')===$level)>{{ ucfirst($level) }}</option>@endforeach</select></div>
            </div>
            <fieldset class="mb-4">
                <legend class="field-label">Game format</legend><div class="grid gap-3 sm:grid-cols-2">
                    @foreach(['doubles' => ['Doubles','Four players per match'], 'singles' => ['Singles','Two players per match']] as $format => [$label,$description])
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition-colors has-[:checked]:border-pine-mid has-[:checked]:bg-pine-soft"><input class="mt-1 accent-pine-mid" type="radio" name="format" value="{{ $format }}" @checked(old('format','doubles')===$format)><span><strong class="block text-sm text-pine">{{ $label }}</strong><small class="mt-1 block text-xs text-muted">{{ $description }}</small></span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="mb-4"><label class="field-label" for="player-limit">Player limit <span class="font-normal text-muted">(optional)</span></label><input id="player-limit" class="field max-w-xs" type="number" name="player_limit" min="1" max="200" value="{{ old('player_limit') }}" placeholder="No limit"></div>
            <div class="mb-4"><label class="field-label" for="rules">Event rules <span class="font-normal text-muted">(optional)</span></label><textarea id="rules" class="field min-h-24 resize-y" name="rules" maxlength="5000" placeholder="Scoring format, arrival notes, or house rules">{{ old('rules') }}</textarea></div>
            <div>
                <div class="mb-2"><label class="field-label mb-0">Courts</label><small class="text-xs text-muted">Optional—add courts later. Queued matches will start when a court is added.</small></div>
                <div class="grid gap-2 sm:grid-cols-2" id="event-courts"></div>
                <button class="btn-secondary mt-3 min-h-9 px-3 py-1.5 text-xs" type="button" id="add-event-court"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a court</button>
            </div>
        </section>
        <div class="space-y-4">
            <section class="ui-card p-5">
                <div class="card-heading"><div><h2>Add players</h2><p>They’ll see the event in their account</p></div></div>
                <label class="field-label" for="registered-players">Registered players</label>
                <select id="registered-players" class="field min-h-36" name="player_ids[]" multiple size="7">
                    @foreach($players as $player)<option value="{{ $player->id }}" @selected(in_array($player->id, old('player_ids', [])))>{{ $player->name }} · {{ $player->email }}</option>@endforeach
                </select>
                <label class="field-label mt-4" for="guest-names">Guest names</label><textarea id="guest-names" class="field min-h-24 resize-y" name="guest_names[]" maxlength="1000" placeholder="Add one guest per line"></textarea>
                <p class="mt-1 text-[11px] text-muted">Each guest can play without creating an account.</p>
            </section>
            <section class="ui-card p-5">
                <div class="card-heading"><div><h2>Assign managers</h2><p>Approved managers can check players in and run courts</p></div></div>
                @forelse($managers as $manager)
                    <label class="flex cursor-pointer items-center gap-3 border-b border-line py-2.5 last:border-0"><input class="accent-pine-mid" type="checkbox" name="manager_ids[]" value="{{ $manager->id }}" @checked(in_array($manager->id, old('manager_ids', [])))><span class="avatar size-8 text-xs">{{ strtoupper(substr($manager->name,0,1)) }}</span><span class="text-xs font-semibold text-pine">{{ $manager->name }}</span></label>
                @empty<p class="text-xs text-muted">No approved Managers yet. You can assign one later.</p>@endforelse
            </section>
            <button class="btn-primary w-full py-3"><i class="bi bi-calendar-check" aria-hidden="true"></i> Create event</button>
        </div>
    </div>
</form>
<script>
document.getElementById('add-event-court').addEventListener('click', () => {
    const list = document.getElementById('event-courts');
    if (list.children.length >= 12) return;
    const number = list.children.length + 1;
    const input = document.createElement('input');
    input.className = 'field';
    input.name = 'courts[]';
    input.placeholder = `Court ${number}`;
    input.setAttribute('aria-label', `Court ${number} name`);
    input.required = true;
    list.appendChild(input);
});
</script>
@endsection
