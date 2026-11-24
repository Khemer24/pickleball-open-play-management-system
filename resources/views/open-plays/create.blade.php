@extends('layouts.app')
@section('title', 'Create open play')
@section('content')
<header class="mb-7 flex items-start justify-between gap-5">
    <div><div class="eyebrow">Host a session</div><h1>Create an open play</h1><p class="subheading">Set the details, add players, and open the courts.</p></div>
    <a href="{{ route('open-plays.index') }}" class="btn-secondary shrink-0">Cancel</a>
</header>
<form method="POST" action="{{ route('open-plays.store') }}">@csrf
<div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(320px,.9fr)]">
    <section class="ui-card p-5 sm:p-6">
        <div class="card-heading"><div><h2>Session details</h2><p>Tell players what they need to know</p></div><i class="bi bi-calendar2-plus text-lg text-pine-mid" aria-hidden="true"></i></div>
        <div class="mb-4"><label class="field-label" for="session-name">Session name</label><input id="session-name" class="field" name="name" value="{{ old('name') }}" placeholder="Saturday morning open play" required></div>
        <div class="mb-4"><label class="field-label" for="venue">Venue</label><input id="venue" class="field" name="venue" value="{{ old('venue') }}" placeholder="Riverside Pickleball Club" required></div>
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div><label class="field-label" for="play-date">Date</label><input id="play-date" class="field" type="date" name="play_date" value="{{ old('play_date', today()->toDateString()) }}" required></div>
            <div><label class="field-label" for="skill-level">Skill level</label><select id="skill-level" class="field" name="skill_level">@foreach(['beginner','intermediate','advanced','all levels'] as $level)<option value="{{ $level }}" @selected(old('skill_level')===$level)>{{ ucfirst($level) }}</option>@endforeach</select></div>
        </div>
        <fieldset class="mb-5">
            <legend class="field-label">Game format</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition-colors has-[:checked]:border-pine-mid has-[:checked]:bg-pine-soft">
                    <input class="mt-1 accent-pine-mid" type="radio" name="format" value="doubles" @checked(old('format','doubles')==='doubles')>
                    <span><strong class="block text-sm text-pine">Doubles</strong><small class="mt-1 block text-xs text-muted">4 players per court</small></span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition-colors has-[:checked]:border-pine-mid has-[:checked]:bg-pine-soft">
                    <input class="mt-1 accent-pine-mid" type="radio" name="format" value="singles" @checked(old('format')==='singles')>
                    <span><strong class="block text-sm text-pine">Singles</strong><small class="mt-1 block text-xs text-muted">2 players per court</small></span>
                </label>
            </div>
        </fieldset>
        <div>
            <div class="mb-2"><label class="field-label mb-0">Courts</label><small class="text-xs text-muted">Name each court. You can add up to 12 courts.</small></div>
            <div class="grid gap-2 sm:grid-cols-2" id="courts-list">@for($court=1;$court<=2;$court++)<input class="field" aria-label="Court {{ $court }} name" name="courts[]" value="{{ old('courts.'.($court-1), 'Court '.$court) }}" required>@endfor</div>
            <button class="btn-secondary mt-3 min-h-9 px-3 py-1.5 text-xs" type="button" id="add-court"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add another court</button>
        </div>
    </section>
    <div>
        <section class="ui-card p-5 sm:p-6">
            <div class="card-heading"><div><h2>Add registered players</h2><p>They’ll see this session right away</p></div><i class="bi bi-people text-lg text-pine-mid" aria-hidden="true"></i></div>
            @if($groups->isNotEmpty())
                <div class="mb-4"><p class="field-label">Reuse a saved group</p><div class="max-h-36 overflow-y-auto rounded-xl border border-line p-3">
                    @foreach($groups as $group)<label class="flex cursor-pointer items-center gap-2 py-1.5 text-xs text-ink"><input class="accent-pine-mid" type="checkbox" name="group_ids[]" value="{{ $group->id }}"> {{ $group->name }} <span class="text-muted">· {{ $group->players->count() }} players</span></label>@endforeach
                </div></div>
            @endif
            <div class="mb-4 max-h-60 overflow-y-auto rounded-xl border border-line p-2">
                @forelse($players as $player)
                    <label class="flex cursor-pointer items-center gap-3 border-b border-line px-2 py-2 last:border-0"><input class="accent-pine-mid" type="checkbox" name="player_ids[]" value="{{ $player->id }}" @checked(in_array($player->id, old('player_ids', [])))><span class="avatar size-8 text-xs">{{ strtoupper(substr($player->name,0,1)) }}</span><span class="text-xs font-medium text-ink">{{ $player->name }}</span></label>
                @empty<p class="p-2 text-xs text-muted">No player accounts yet. Share the registration link to invite your community.</p>@endforelse
            </div>
            <label class="field-label" for="guest-1">Add guests by name</label>
            <div class="space-y-2">@for($guest=0;$guest<4;$guest++)<input id="guest-{{ $guest+1 }}" class="field" name="guest_names[]" value="{{ old('guest_names.'.$guest) }}" placeholder="Guest name {{ $guest+1 }}">@endfor</div>
        </section>
        <button class="btn-primary mt-4 w-full py-3">Create session <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
</div>
</form>
<script>
document.getElementById('add-court').addEventListener('click', () => {
    const list = document.getElementById('courts-list');
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
