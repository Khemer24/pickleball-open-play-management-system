<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102d23">
    <meta http-equiv="refresh" content="20">
    <title>{{ $session->name }} · RallyCourt live</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen min-w-[320px] bg-paper font-sans text-ink antialiased">
    <div class="min-h-screen">
        <nav class="bg-pine-deep text-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-7">
                <a class="flex items-center gap-2.5 text-base font-extrabold tracking-tight text-white hover:text-white" href="{{ route('login') }}">
                    <span class="grid size-9 place-items-center rounded-xl border border-white/20 bg-white/10 text-sm text-lime">R</span>
                    <span>RallyCourt</span>
                </a>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white">
                        <span class="size-2 rounded-full bg-lime shadow-[0_0_0_3px_rgba(201,238,100,0.15)]" aria-hidden="true"></span>
                        Public live board
                    </span>
                    <span class="hidden text-xs text-white/60 sm:inline">Refreshes every 20 seconds</span>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-4 py-5 sm:px-7 sm:py-8 lg:px-10">
            <header class="relative isolate overflow-hidden rounded-[1.75rem] bg-pine-deep px-5 py-6 text-white shadow-xl shadow-pine-deep/10 sm:px-8 sm:py-8">
                <div class="absolute -right-20 -top-32 -z-10 size-80 rounded-full border-[36px] border-white/[0.04]" aria-hidden="true"></div>
                <div class="absolute -bottom-36 right-28 -z-10 size-64 rounded-full border-[24px] border-lime/[0.08]" aria-hidden="true"></div>
                <div class="relative grid gap-6 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <div class="min-w-0">
                        @if($session->finalized_at)
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white/80"><i class="bi bi-flag-fill text-lime" aria-hidden="true"></i> Event complete</span>
                        @elseif($activeMatchCount)
                            <span class="inline-flex items-center gap-2 rounded-full bg-lime px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-pine-deep"><span class="size-1.5 animate-pulse rounded-full bg-pine-deep" aria-hidden="true"></span> Live now</span>
                        @elseif($queueCount)
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-lime"><i class="bi bi-lightning-fill" aria-hidden="true"></i> Matches up next</span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white/75"><i class="bi bi-calendar-check" aria-hidden="true"></i> Open play</span>
                        @endif
                        <h1 class="mt-4 break-words text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $session->name }}</h1>
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/75">
                            <span class="inline-flex items-center gap-2"><i class="bi bi-geo-alt text-lime" aria-hidden="true"></i>{{ $session->venue }}</span>
                            <span class="inline-flex items-center gap-2"><i class="bi bi-calendar3 text-lime" aria-hidden="true"></i>{{ $session->starts_at?->format('D, M j · g:i A') ?? $session->play_date->format('D, M j') }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-white/15 bg-white/[0.07] px-3 py-1 text-[11px] font-semibold text-white/80">{{ ucfirst($session->skill_level) }}</span>
                            <span class="rounded-full border border-white/15 bg-white/[0.07] px-3 py-1 text-[11px] font-semibold text-white/80">{{ ucfirst($session->format) }}</span>
                            <span class="text-xs text-white/65">Hosted by <strong class="font-semibold text-white">{{ $session->organizer?->name ?? $session->manager?->name ?? 'RallyCourt' }}</strong></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 md:min-w-52">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-3.5">
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-white/55">Players in</span>
                            <span class="mt-1 block text-2xl font-extrabold text-white">{{ $checkedInCount }}<span class="ml-1 text-xs font-medium text-white/50">/ {{ $session->players->count() }}</span></span>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-3.5">
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-white/55">Courts live</span>
                            <span class="mt-1 block text-2xl font-extrabold text-lime">{{ $activeMatchCount }}<span class="ml-1 text-xs font-medium text-white/50">/ {{ $session->courts->count() }}</span></span>
                        </div>
                    </div>
                </div>
            </header>

            <section aria-label="Session overview" class="my-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article class="rounded-2xl border border-line bg-surface px-4 py-3.5 shadow-card">
                    <div class="flex items-center gap-2 text-xs font-semibold text-muted"><span class="grid size-8 place-items-center rounded-xl bg-pine-soft text-pine"><i class="bi bi-people" aria-hidden="true"></i></span>Players checked in</div>
                    <p class="mt-3 text-2xl font-extrabold tracking-tight text-pine">{{ $checkedInCount }}</p>
                </article>
                <article class="rounded-2xl border border-line bg-surface px-4 py-3.5 shadow-card">
                    <div class="flex items-center gap-2 text-xs font-semibold text-muted"><span class="grid size-8 place-items-center rounded-xl bg-[#eff4df] text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i></span>Courts in play</div>
                    <p class="mt-3 text-2xl font-extrabold tracking-tight text-pine">{{ $activeMatchCount }}<span class="ml-1 text-sm font-semibold text-muted">/ {{ $session->courts->count() }}</span></p>
                </article>
                <article class="rounded-2xl border border-line bg-surface px-4 py-3.5 shadow-card">
                    <div class="flex items-center gap-2 text-xs font-semibold text-muted"><span class="grid size-8 place-items-center rounded-xl bg-[#eff4df] text-pine"><i class="bi bi-lightning" aria-hidden="true"></i></span>Players up next</div>
                    <p class="mt-3 text-2xl font-extrabold tracking-tight text-pine">{{ $queueCount }}</p>
                </article>
                <article class="rounded-2xl border border-line bg-surface px-4 py-3.5 shadow-card">
                    <div class="flex items-center gap-2 text-xs font-semibold text-muted"><span class="grid size-8 place-items-center rounded-xl bg-pine-soft text-pine"><i class="bi bi-trophy" aria-hidden="true"></i></span>Matches complete</div>
                    <p class="mt-3 text-2xl font-extrabold tracking-tight text-pine">{{ $completedMatchCount }}</p>
                </article>
            </section>

            <section aria-labelledby="courts-heading" class="mb-5">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-pine-mid">On the court</p><h2 id="courts-heading" class="mt-1 text-xl font-extrabold tracking-tight text-pine sm:text-2xl">Court action</h2></div>
                    <span class="text-xs text-muted">{{ $activeMatchCount }} {{ \Illuminate\Support\Str::plural('match', $activeMatchCount) }} in progress</span>
                </div>
                @if($session->courts->isEmpty())
                    <div class="rounded-2xl border border-dashed border-court-line bg-pine-soft/60 px-5 py-8 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-2xl bg-white text-xl text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i></span>
                        <h3 class="mt-3 text-sm font-bold text-pine">Courts coming soon</h3>
                        <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-muted">Players are checked in and matchups are ready. Court updates will appear here as soon as play begins.</p>
                    </div>
                @else
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($session->courts as $court)
                            @php($match = $court->matches->first())
                            <article @class(['overflow-hidden rounded-2xl border bg-surface shadow-card transition-shadow', 'border-court-line shadow-pine/5' => $match, 'border-line' => !$match])>
                                <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-pine-soft text-pine"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></span>
                                        <div class="min-w-0"><span class="block text-[10px] font-bold uppercase tracking-wider text-muted">Court</span><h3 class="truncate text-sm font-bold text-pine">{{ $court->name }}</h3></div>
                                    </div>
                                    @if($match)
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#e6f0e5] px-2.5 py-1 text-[10px] font-extrabold text-[#285742]"><span class="size-1.5 animate-pulse rounded-full bg-[#3a8955]" aria-hidden="true"></span>Live</span>
                                    @else
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#f0f2ea] px-2.5 py-1 text-[10px] font-bold text-muted"><span class="size-1.5 rounded-full bg-[#aab6ab]" aria-hidden="true"></span>Available</span>
                                    @endif
                                </div>
                                @if($match)
                                    <div class="space-y-2.5 p-4">
                                        @foreach([1, 2] as $team)
                                            <div @class(['rounded-xl px-3 py-2.5', 'bg-pine-soft' => $team === 1, 'bg-[#f5f5ee]' => $team === 2])>
                                                <span class="mb-1.5 block text-[9px] font-extrabold uppercase tracking-[.12em] text-pine-mid">Team {{ $team }}</span>
                                                <div class="space-y-1.5">
                                                    @foreach($match->participants->where('pivot.team', $team) as $player)
                                                        <div class="flex min-w-0 items-center gap-2">
                                                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-white/80 text-[10px] font-bold text-pine">{{ strtoupper(substr($player->displayName(), 0, 1)) }}</span>
                                                            <span class="min-w-0 truncate text-xs font-semibold text-pine">{{ $player->displayName() }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="flex items-center justify-between border-t border-line pt-2.5 text-[10px] text-muted">
                                            <span>Started {{ $match->started_at?->format('g:i A') }}</span>
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-pine-deep px-2.5 py-1.5 font-mono font-bold text-white" data-live-elapsed="{{ $match->started_at?->timestamp }}" aria-label="Match elapsed time">00:00</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex min-h-32 flex-col items-center justify-center px-4 py-6 text-center">
                                        <span class="grid size-9 place-items-center rounded-full bg-[#f0f2ea] text-muted"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                                        <p class="mt-2 text-xs font-semibold text-pine">Ready for the next match</p>
                                        <p class="mt-0.5 text-[10px] text-muted">This court is available</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)]">
                <section aria-labelledby="queue-heading" class="min-w-0 rounded-2xl border border-line bg-surface p-4 shadow-card sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-pine-mid">Coming up</p><h2 id="queue-heading" class="mt-1 text-lg font-extrabold tracking-tight text-pine">Up next</h2><p class="mt-1 text-xs text-muted">{{ $queueCount }} {{ \Illuminate\Support\Str::plural('player', $queueCount) }} in the queue</p></div>
                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-[#eff4df] text-pine"><i class="bi bi-list-ol text-lg" aria-hidden="true"></i></span>
                    </div>
                    @if($queueMode)
                        <div class="space-y-2.5">
                            @forelse($queuedMatches as $index => $match)
                                @php($teamOne = $match->participants->where('pivot.team', 1))
                                @php($teamTwo = $match->participants->where('pivot.team', 2))
                                <article @class(['rounded-xl border p-3 sm:p-3.5', 'border-lime bg-[#f5f8e9]' => $index === 0, 'border-line bg-surface' => $index !== 0])>
                                    <div class="mb-2.5 flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Match {{ $index + 1 }}</span>
                                        @if($index === 0)<span class="inline-flex items-center gap-1 rounded-full bg-lime px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wide text-pine-deep"><i class="bi bi-lightning-fill" aria-hidden="true"></i> Up next</span>@else<span class="ui-badge">Queued</span>@endif
                                    </div>
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 text-center">
                                        <div class="space-y-1.5">@foreach($teamOne as $player)<div class="rounded-lg bg-white px-2 py-2 text-[11px] font-semibold leading-4 text-pine">{{ $player->displayName() }}</div>@endforeach</div>
                                        <span class="grid size-7 place-items-center rounded-full border border-line bg-white text-[8px] font-extrabold text-muted">VS</span>
                                        <div class="space-y-1.5">@foreach($teamTwo as $player)<div class="rounded-lg bg-white px-2 py-2 text-[11px] font-semibold leading-4 text-pine">{{ $player->displayName() }}</div>@endforeach</div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl bg-paper px-4 py-6 text-center">
                                    <i class="bi bi-check2-circle text-xl text-pine-mid" aria-hidden="true"></i>
                                    <p class="mt-2 text-xs font-semibold text-pine">No matchups waiting</p>
                                    <p class="mt-1 text-[11px] text-muted">New matches appear here when players are ready.</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="mt-4 border-t border-line pt-3">
                            <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-bold text-pine">On the bench</h3><span class="ui-badge">{{ $waiting->count() }}</span></div>
                            @forelse($waiting as $player)
                                <span class="mb-1.5 mr-1 inline-flex items-center gap-1.5 rounded-full border border-line bg-paper px-2.5 py-1.5 text-[10px] font-semibold text-pine"><span class="size-1.5 rounded-full bg-[#879b88]" aria-hidden="true"></span>{{ $player->displayName() }}</span>
                            @empty
                                <p class="text-[10px] text-muted">Everyone checked in has a matchup.</p>
                            @endforelse
                        </div>
                    @else
                        <div class="divide-y divide-line">
                            @forelse($waiting as $index => $player)
                                <div class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-pine-soft text-[10px] font-bold text-pine">{{ $index + 1 }}</span>
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#eff4df] text-[10px] font-bold text-pine">{{ strtoupper(substr($player->displayName(), 0, 1)) }}</span>
                                    <span class="min-w-0 flex-1 truncate text-xs font-semibold text-pine">{{ $player->displayName() }}</span>
                                    <span class="shrink-0 text-[10px] text-muted">{{ $player->games_played }} {{ \Illuminate\Support\Str::plural('game', $player->games_played) }}</span>
                                </div>
                            @empty
                                <div class="rounded-xl bg-paper px-4 py-6 text-center">
                                    <i class="bi bi-check2-circle text-xl text-pine-mid" aria-hidden="true"></i>
                                    <p class="mt-2 text-xs font-semibold text-pine">No players waiting</p>
                                    <p class="mt-1 text-[11px] text-muted">The queue will update as players check in.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </section>

                <section aria-labelledby="standings-heading" class="min-w-0 rounded-2xl border border-line bg-surface p-4 shadow-card sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-pine-mid">The leaderboard</p><h2 id="standings-heading" class="mt-1 text-lg font-extrabold tracking-tight text-pine">Standings</h2><p class="mt-1 text-xs text-muted">Ranked by wins, then point differential</p></div>
                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-pine-soft text-pine"><i class="bi bi-trophy text-lg" aria-hidden="true"></i></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[320px] border-separate border-spacing-0 text-left">
                            <thead><tr class="text-[9px] font-bold uppercase tracking-wider text-muted"><th scope="col" class="border-b border-line px-2 py-2">Rank</th><th scope="col" class="border-b border-line px-2 py-2">Player</th><th scope="col" class="border-b border-line px-2 py-2 text-center">W–L</th><th scope="col" class="border-b border-line px-2 py-2 text-right">Diff</th></tr></thead>
                            <tbody>
                                @forelse($standings as $index => $row)
                                    <tr class="group">
                                        <td class="border-b border-line px-2 py-3 last:border-0"><span @class(['grid size-7 place-items-center rounded-full text-[10px] font-extrabold', 'bg-lime text-pine-deep' => $index === 0, 'bg-pine-soft text-pine' => $index > 0])>{{ $index + 1 }}</span></td>
                                        <th scope="row" class="max-w-0 truncate border-b border-line px-2 py-3 text-xs font-semibold text-pine">{{ $row['player']->displayName() }}</th>
                                        <td class="border-b border-line px-2 py-3 text-center text-xs font-semibold text-pine">{{ $row['wins'] }}–{{ $row['losses'] }}</td>
                                        <td class="border-b border-line px-2 py-3 text-right text-xs font-bold {{ $row['diff'] > 0 ? 'text-[#367048]' : ($row['diff'] < 0 ? 'text-[#9a5c4d]' : 'text-muted') }}">{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-8 text-center text-xs text-muted">Player standings will appear once the roster is set.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($completedMatchCount === 0 && $standings->isNotEmpty())
                        <p class="mt-3 rounded-xl bg-paper px-3 py-2.5 text-[10px] text-muted"><i class="bi bi-info-circle mr-1 text-pine-mid" aria-hidden="true"></i>Results will update here as matches are completed.</p>
                    @endif
                </section>
            </div>

            <footer class="mt-6 flex flex-col items-center justify-between gap-2 border-t border-line py-4 text-center sm:flex-row sm:text-left">
                <span class="text-[10px] font-semibold text-muted">Live scores by <span class="text-pine">RallyCourt</span></span>
                <span class="inline-flex items-center gap-1.5 text-[10px] text-muted"><i class="bi bi-arrow-repeat text-pine-mid" aria-hidden="true"></i> This board refreshes automatically every 20 seconds</span>
            </footer>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-live-elapsed]').forEach((timer) => {
            const update = () => {
                const seconds = Math.max(0, Math.floor(Date.now() / 1000 - Number(timer.dataset.liveElapsed)));
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                timer.textContent = hours
                    ? `${String(hours).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
                    : `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
            };
            update();
            setInterval(update, 1000);
        });
    </script>
</body>
</html>
