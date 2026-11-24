@extends('layouts.app')
@section('title', 'Players played with')
@section('content')
<header class="mb-6"><div class="eyebrow">Your court community</div><h1>Players played with</h1><p class="subheading">Everyone you’ve shared a completed match with—as a teammate or opponent.</p></header>
<section class="ui-card panel-card">
    <form method="GET" action="{{ route('players.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-[220px] flex-1"><label class="sr-only" for="player-search">Search by name</label><input id="player-search" class="field" name="search" value="{{ $search }}" placeholder="Search by name"></div>
        <div class="w-full sm:w-56"><label class="sr-only" for="sort">Sort players</label><select class="field" id="sort" name="sort"><option value="last_played" @selected($sort==='last_played')>Most recently played</option><option value="win_rate" @selected($sort==='win_rate')>Highest win rate</option><option value="record" @selected($sort==='record')>Best W–L record</option><option value="name" @selected($sort==='name')>Name A–Z</option></select></div>
        <button class="btn-secondary">Apply</button>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-xs">
            <thead class="rounded-xl bg-[#f0f2ea] text-[10px] uppercase tracking-wide text-muted"><tr><th class="rounded-l-xl px-3 py-3">Player</th><th class="px-3 py-3">Win rate</th><th class="px-3 py-3">Record</th><th class="px-3 py-3">Recent results</th><th class="rounded-r-xl px-3 py-3">Last played</th></tr></thead>
            <tbody>
                @forelse($players as $player)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-3 py-3"><div class="flex items-center gap-2.5">@if($player['avatar_path'])<img class="avatar size-8" src="{{ Storage::url($player['avatar_path']) }}" alt="">@else<span class="avatar size-8 text-[10px]">{{ strtoupper(substr($player['name'],0,1)) }}</span>@endif<strong class="font-semibold text-pine">{{ $player['name'] }}</strong></div></td>
                        <td class="px-3 py-3"><span class="font-bold {{ $player['rate'] >= 50 ? 'text-[#17814b]' : 'text-[#b34545]' }}">{{ $player['rate'] }}%</span></td>
                        <td class="px-3 py-3"><span class="font-semibold text-[#17814b]">{{ $player['wins'] }}W</span><span class="mx-1 text-muted">–</span><span class="font-semibold text-[#b34545]">{{ $player['losses'] }}L</span></td>
                        <td class="px-3 py-3"><div class="flex items-center gap-1" aria-label="Recent match results">@forelse($player['recent'] as $result)<span class="inline-grid size-5 place-items-center rounded-full text-[9px] font-bold text-white {{ $result['won'] ? 'bg-[#159447]' : 'bg-[#cf3038]' }}" title="{{ $result['session'] }} · {{ $result['date']->format('M j') }}">{{ $result['won'] ? 'W' : 'L' }}</span>@empty<span class="text-muted">—</span>@endforelse</div></td>
                        <td class="px-3 py-3 text-muted">{{ $player['last_played']?->format('M j, Y, g:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-12 text-center"><div class="empty-state-icon mb-2"><i class="bi bi-people" aria-hidden="true"></i></div><strong class="block text-sm text-pine">{{ $search ? 'No players found' : 'No completed matches yet' }}</strong><span class="mt-1 block text-xs text-muted">{{ $search ? 'Try another name.' : 'Players from your completed matches will appear here.' }}</span></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $players->links() }}</div>
</section>
@if(auth()->user()->isManager())
    <section class="ui-card panel-card">
        <div class="card-heading"><div><h2>Saved player groups</h2><p>Keep a regular crew ready to add to your next session.</p></div></div>
        <form method="POST" action="{{ route('player-groups.store') }}" class="mb-4 grid items-end gap-3 md:grid-cols-[minmax(0,.8fr)_minmax(0,1.3fr)_auto]">@csrf
            <div><label class="field-label" for="group-name">Group name</label><input id="group-name" class="field" name="name" placeholder="Tuesday regulars" required></div>
            <div><label class="field-label" for="group-players">Players to include</label><select id="group-players" class="field min-h-[72px]" name="player_ids[]" multiple size="2">@foreach($registeredPlayers as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></div><button class="btn-primary">Save group</button>
        </form>
        @forelse($groups as $group)<span class="ui-badge mr-1">{{ $group->name }} · {{ $group->players_count }} players</span>@empty<p class="text-xs text-muted">No saved groups yet.</p>@endforelse
    </section>
@endif
@endsection
