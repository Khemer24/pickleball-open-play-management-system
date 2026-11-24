@extends('layouts.app')
@section('title', 'Manage '.$session->name)
@section('content')
<header class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
    <div><div class="eyebrow"><a class="hover:text-pine" href="{{ route('open-plays.index') }}">Open plays</a> / Court control</div><h1>{{ $session->name }}</h1><p class="subheading">{{ $session->venue }} · {{ $session->play_date->format('D, M j') }} · {{ ucfirst($session->format) }}</p></div>
    <div class="flex flex-wrap items-center gap-2"><button class="btn-secondary" type="button" data-copy-live-link="{{ route('open-plays.live', $session->share_token) }}"><i class="bi bi-link-45deg" aria-hidden="true"></i> Copy live link</button><a class="btn-primary" href="{{ route('open-plays.live', $session->share_token) }}" target="_blank" rel="noopener"><i class="bi bi-eye" aria-hidden="true"></i> Preview board</a><span data-copy-live-status class="text-xs text-muted" role="status" aria-live="polite"></span></div>
</header>
<details class="ui-card mb-4 p-5">
    <summary class="cursor-pointer font-bold text-pine">Edit session details</summary>
    <form method="POST" action="{{ route('open-plays.update',$session) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@csrf @method('PUT')
        <div><label class="field-label" for="edit-name">Session name</label><input id="edit-name" class="field" name="name" value="{{ $session->name }}" required></div>
        <div><label class="field-label" for="edit-venue">Venue</label><input id="edit-venue" class="field" name="venue" value="{{ $session->venue }}" required></div>
        <div><label class="field-label" for="edit-date">Date</label><input id="edit-date" class="field" type="date" name="play_date" value="{{ $session->play_date->toDateString() }}" required></div>
        <div><label class="field-label" for="edit-skill">Skill level</label><select id="edit-skill" class="field" name="skill_level">@foreach(['beginner','intermediate','advanced','all levels'] as $level)<option value="{{ $level }}" @selected($session->skill_level === $level)>{{ ucfirst($level) }}</option>@endforeach</select></div>
        <div class="flex items-center rounded-xl bg-paper px-3 text-xs text-muted">{{ ucfirst($session->format) }} · {{ $session->courts->count() }} named courts</div>
        <div class="sm:col-span-2 lg:col-span-3"><button class="btn-primary">Save session details</button></div>
    </form>
</details>
@if($session->finalized_at)<div class="status-message status-success mb-4" role="status">This session was finalized on {{ $session->finalized_at->format('M j, g:i A') }}. Scores and standings are locked.</div>@endif
<section class="mb-6 grid gap-3 sm:grid-cols-3">
    <article class="ui-card p-4"><div class="text-xs font-semibold text-muted">Players checked in</div><div class="mt-1 text-3xl font-extrabold text-pine">{{ $session->players->whereNotNull('checked_in_at')->count() }}</div><div class="mt-1 text-xs text-muted">of {{ $session->players->count() }} on the roster</div></article>
    <article class="ui-card p-4"><div class="text-xs font-semibold text-muted">Courts in play</div><div class="mt-1 text-3xl font-extrabold text-pine">{{ $session->matches->where('status','playing')->count() }}<span class="text-base font-semibold text-muted"> / {{ $session->courts->count() }}</span></div><div class="mt-1 text-xs text-muted">Games running now</div></article>
    <article class="ui-card p-4"><div class="text-xs font-semibold text-muted">Waiting</div><div class="mt-1 text-3xl font-extrabold text-pine">{{ $waiting->count() }}</div><div class="mt-1 text-xs text-muted">Sorted by games played, then wait time</div></article>
</section>
<div class="mb-3 flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-xl font-bold text-pine">Active courts</h2><p class="mt-1 text-xs text-muted">Finishing one game won’t interrupt others</p></div></div>
<div class="mb-6 grid gap-3 md:grid-cols-2">
@foreach($session->courts as $court)
    @php($match = $court->matches->first())
    <article class="ui-card court-card h-full">
        <div class="court-top"><strong>{{ $court->name }}</strong>@if($match)<span class="court-live"><i class="bi bi-circle-fill" aria-hidden="true"></i> IN PLAY</span>@else<span class="ui-badge">Available</span>@endif</div>
        @if($match)
            @php($teamOne=$match->participants->where('pivot.team',1))
            @php($teamTwo=$match->participants->where('pivot.team',2))
            <div class="court-team"><strong>Team 1</strong><div>{{ $teamOne->map(fn($p)=>$p->displayName())->join(' & ') }}</div></div>
            <div class="court-team"><strong>Team 2</strong><div>{{ $teamTwo->map(fn($p)=>$p->displayName())->join(' & ') }}</div></div>
            <div class="court-score mb-2"><span>Started {{ $match->started_at->format('g:i A') }}</span><span data-start="{{ $match->started_at->timestamp }}" class="elapsed" aria-label="Match elapsed time">00:00</span></div>
            @if(!$session->finalized_at)
            <form method="POST" action="{{ route('open-plays.score',[$session,$match]) }}" class="mt-3 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-end gap-2">
                @csrf
                <div><label class="field-label mb-1 text-xs" for="team-one-{{ $match->id }}">Team 1</label><input id="team-one-{{ $match->id }}" type="number" min="0" max="99" name="team_one_score" class="field" placeholder="Score" required></div>
                <span class="pb-2 text-muted">–</span>
                <div><label class="field-label mb-1 text-xs" for="team-two-{{ $match->id }}">Team 2</label><input id="team-two-{{ $match->id }}" type="number" min="0" max="99" name="team_two_score" class="field" placeholder="Score" required></div>
                <button class="btn-primary">Submit</button>
            </form>
            @endif
        @else
            <div class="flex flex-col items-center justify-center py-7 text-center text-muted"><i class="bi bi-hourglass-split empty-state-icon mb-2" aria-hidden="true"></i><span class="text-xs">Waiting for enough checked-in players</span></div>
        @endif
    </article>
@endforeach
</div>
<div class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(300px,.9fr)]">
    <div class="min-w-0">
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Player roster</h2><p>Check players in as they arrive. Matches will start automatically.</p></div><span class="ui-badge">{{ $session->players->count() }} players</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[550px] text-left text-xs"><thead class="border-b border-line text-[10px] uppercase tracking-wide text-muted"><tr><th class="py-2 pr-3">Player</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Games</th><th class="py-2 text-right"></th></tr></thead><tbody>
                @foreach($session->players->sortBy(fn($p)=>$p->checked_in_at ? 0 : 1) as $player)
                    @php($onCourt=$session->courts->flatMap(fn($court)=>$court->matches->flatMap(fn($game)=>$game->participants))->contains('id',$player->id))
                    <tr class="border-b border-line last:border-0"><td class="py-3 pr-3 font-semibold text-pine">{{ $player->displayName() }} @if(!$player->user_id)<span class="ui-badge ml-1">Guest</span>@endif</td>
                        <td class="py-3 pr-3">@if($onCourt)<span class="court-live">Playing</span>@elseif($player->checked_in_at)<span class="ui-badge">Waiting</span>@else<span class="text-muted">Not checked in</span>@endif</td>
                        <td class="py-3 pr-3 text-ink">{{ $player->matchAppearances->count() }}</td><td class="py-3 text-right">@if(!$player->checked_in_at && !$session->finalized_at)<form method="POST" action="{{ route('open-plays.check-in',[$session,$player]) }}">@csrf<button class="btn-outline min-h-8 px-2.5 py-1 text-[11px]">Check in</button></form>@endif</td>
                    </tr>
                @endforeach
                @if($session->players->isEmpty())<tr><td colspan="4" class="py-6 text-center text-muted">No players added yet.</td></tr>@endif
            </tbody></table></div>
            @if(!$session->finalized_at)
            <form method="POST" action="{{ route('open-plays.players.store',$session) }}" class="mt-4 border-t border-line pt-4">@csrf
                <div class="grid items-end gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto]"><div><label class="field-label" for="registered-player">Registered player</label><select id="registered-player" class="field" name="user_id"><option value="">Choose player…</option>@foreach($registeredPlayers as $person)<option value="{{ $person->id }}">{{ $person->name }} · {{ $person->email }}</option>@endforeach</select></div>
                <div><label class="field-label" for="guest-name">Or add a guest</label><input id="guest-name" class="field" name="guest_name" placeholder="Guest name"></div><button class="btn-secondary"><i class="bi bi-person-plus" aria-hidden="true"></i> Add player</button></div>
            </form>
            @endif
        </section>
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Waiting queue</h2><p>Players move up automatically as courts free up</p></div></div>
            @forelse($waiting as $index=>$player)<div class="person-row"><span class="queue-num">{{ $index+1 }}</span><div class="person-info"><strong>{{ $player->displayName() }}</strong><span>{{ $player->games_played }} {{ \Illuminate\Support\Str::plural('game',$player->games_played) }} played · checked in {{ $player->checked_in_at->diffForHumans() }}</span></div><span class="ui-badge shrink-0">{{ $player->checked_in_at->format('g:i A') }}</span></div>
            @empty<p class="text-xs text-muted">No checked-in players are waiting right now.</p>@endforelse
        </section>
    </div>
    <div class="min-w-0">
        <section class="ui-card panel-card">
            <div class="card-heading"><div><h2>Standings</h2><p>Ranked by wins, then point differential</p></div></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[300px] text-left text-xs"><thead class="border-b border-line text-[10px] uppercase tracking-wide text-muted"><tr><th class="py-2 pr-2">#</th><th class="py-2 pr-2">Player</th><th class="py-2 pr-2">W</th><th class="py-2">Diff</th></tr></thead><tbody>
            @foreach($standings as $index=>$row)<tr class="border-b border-line last:border-0"><td class="py-2.5 pr-2">@if($index<3)<span class="inline-flex size-6 items-center justify-center rounded-full {{ $index===0?'bg-lime text-pine':'bg-pine-soft text-pine' }}">{{ $index+1 }}</span>@else{{ $index+1 }}@endif</td><td class="py-2.5 pr-2 font-semibold text-pine">{{ $row['player']->displayName() }}</td><td class="py-2.5 pr-2">{{ $row['wins'] }}</td><td class="py-2.5">{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</td></tr>@endforeach
            @if($standings->isEmpty())<tr><td colspan="4" class="py-5 text-center text-muted">Scores appear here as games finish.</td></tr>@endif
            </tbody></table></div>
            @if(!$session->finalized_at)<form method="POST" action="{{ route('open-plays.finalize',$session) }}" class="mt-4 border-t border-line pt-4">@csrf<button class="btn-outline w-full" {{ $session->matches->where('status','playing')->isNotEmpty() ? 'disabled' : '' }}><i class="bi bi-lock" aria-hidden="true"></i> Finalize session & lock podium</button><small class="mt-2 block text-center text-muted">Available once all active matches are finished.</small></form>@endif
        </section>
    </div>
</div>
<script>
document.querySelectorAll('.elapsed').forEach((timer) => {
    const update = () => { const seconds = Math.max(0, Math.floor(Date.now()/1000 - Number(timer.dataset.start))); timer.textContent = `${String(Math.floor(seconds/60)).padStart(2,'0')}:${String(seconds%60).padStart(2,'0')}`; };
    update(); setInterval(update, 1000);
});
</script>
@endsection
