<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Event workspace') · RallyCourt</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen min-w-[320px] bg-paper font-sans text-ink antialiased">
    @php($workspaceTab = $tab ?? request()->query('tab', 'overview'))
    @php($workspaceLinks = [
        'overview' => ['Overview', 'bi-grid-1x2'],
        'courts' => ['Courts & queue', 'bi-grid-3x3'],
        'players' => ['Players', 'bi-people'],
        'ranks' => ['Ranks & history', 'bi-trophy'],
    ])
    <div class="min-h-screen lg:flex">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-pine-deep px-4 py-6 text-white lg:flex">
            <a class="flex items-center gap-2.5 px-2 text-lg font-extrabold tracking-tight text-white hover:text-white" href="{{ route('dashboard') }}">
                <span class="grid size-9 place-items-center rounded-xl border border-white/15 bg-white/10 text-lime">R</span>
                <span>RallyCourt</span>
            </a>
            <p class="mb-3 mt-10 px-3 text-[10px] font-bold uppercase tracking-[.15em] text-white/45">Session workspace</p>
            <nav aria-label="Session workspace" class="space-y-1">
                @foreach($workspaceLinks as $key => [$label, $icon])
                    <a href="{{ route('events.show', [$session, 'tab' => $key]) }}" @class([
                        'flex min-h-11 items-center gap-3 rounded-xl px-3 text-[13px] font-semibold transition-colors',
                        'bg-lime text-pine-deep shadow-sm' => $workspaceTab === $key,
                        'text-white/75 hover:bg-white/10 hover:text-white' => $workspaceTab !== $key,
                    ]) @if($workspaceTab === $key) aria-current="page" @endif>
                        <i class="bi {{ $icon }} w-5 text-center text-base" aria-hidden="true"></i>{{ $label }}
                    </a>
                @endforeach
            </nav>
            <a href="{{ route('open-plays.index') }}" class="mt-4 flex min-h-10 items-center gap-3 rounded-xl px-3 text-xs font-semibold text-white/60 transition-colors hover:bg-white/10 hover:text-white">
                <i class="bi bi-arrow-left w-5 text-center" aria-hidden="true"></i>Back to open plays
            </a>
            <div class="mt-auto border-t border-white/10 pt-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2 transition-colors hover:bg-white/5">
                    @if(auth()->user()->avatar_path)
                        <img class="size-9 rounded-full object-cover" src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="">
                    @else
                        <span class="grid size-9 place-items-center rounded-full bg-lime/20 text-xs font-bold text-lime">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</span>
                        <span class="block text-[10px] text-white/50">{{ ucfirst(auth()->user()->role) }}</span>
                    </span>
                    <i class="bi bi-person-gear text-white/45" aria-hidden="true"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                    <button class="flex min-h-9 w-full items-center gap-3 rounded-lg px-3 text-xs font-semibold text-white/60 hover:bg-white/10 hover:text-white" type="submit"><i class="bi bi-box-arrow-right w-5 text-center" aria-hidden="true"></i>Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="border-b border-line bg-surface">
                <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-7 lg:px-9">
                    <div class="flex min-w-0 items-center gap-3">
                        <a class="flex shrink-0 items-center gap-2 text-sm font-extrabold text-pine lg:hidden" href="{{ route('dashboard') }}"><span class="grid size-8 place-items-center rounded-lg bg-pine-deep text-xs text-lime">R</span>RallyCourt</a>
                        <i class="bi bi-chevron-right hidden text-[10px] text-muted lg:block" aria-hidden="true"></i>
                        <div class="min-w-0 text-[11px] text-muted">
                            <a class="hidden hover:text-pine sm:inline" href="{{ route('open-plays.index') }}">Open plays</a>
                            <span class="hidden px-1 sm:inline">/</span>
                            <span class="truncate font-semibold text-pine">{{ $session->name }}</span>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button class="btn-secondary min-h-9 px-3 text-[11px] sm:px-4 sm:text-xs" type="button" data-open-share-dialog>
                            <i class="bi bi-link-45deg" aria-hidden="true"></i><span>Share live link</span>
                        </button>
                        <form class="lg:hidden" method="POST" action="{{ route('logout') }}">@csrf<button class="grid size-9 place-items-center rounded-lg border border-line text-pine" type="submit" aria-label="Sign out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button></form>
                    </div>
                </div>
            </header>

            <nav aria-label="Session workspace" class="grid grid-cols-4 gap-1 border-b border-line bg-surface px-2 py-2 lg:hidden">
                @foreach($workspaceLinks as $key => [$label, $icon])
                    <a href="{{ route('events.show', [$session, 'tab' => $key]) }}" @class([
                        'flex min-h-11 min-w-0 flex-col items-center justify-center gap-1 rounded-lg px-1 text-center text-[9px] font-bold leading-tight transition-colors sm:flex-row sm:text-[11px]',
                        'bg-lime text-pine-deep' => $workspaceTab === $key,
                        'text-muted hover:bg-pine-soft' => $workspaceTab !== $key,
                    ]) @if($workspaceTab === $key) aria-current="page" @endif>
                        <i class="bi {{ $icon }} text-sm" aria-hidden="true"></i><span class="truncate">{{ $label }}</span>
                    </a>
                @endforeach
            </nav>

            <main class="min-w-0 px-4 py-5 sm:px-7 sm:py-7 lg:px-9 lg:py-8">
                @if(session('success'))<div class="status-message status-success mb-4" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())
                    <div class="status-message status-error mb-4" role="alert"><strong>Please check the form:</strong><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <dialog id="share-live-dialog" aria-labelledby="share-live-title" class="m-auto w-[calc(100%-2rem)] max-w-[440px] rounded-2xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-pine-deep/60">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="share-live-title" class="text-lg font-extrabold text-pine">Share live scoreboard</h2><p class="mt-1 text-xs leading-5 text-muted">Share a read-only view of the courts, queue, and standings.</p></div>
                <button class="grid size-9 shrink-0 place-items-center rounded-lg border border-line text-muted transition-colors hover:bg-paper hover:text-pine" type="button" data-close-share-dialog aria-label="Close dialog"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <label class="field-label mt-5" for="spectator-link">Spectator link</label>
            <input id="spectator-link" class="field min-h-10 text-xs" type="url" readonly value="{{ route('open-plays.live', $session->share_token) }}" onclick="this.select()">
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button class="btn-primary min-h-9 px-3 text-xs" type="button" data-copy-live-link="{{ route('open-plays.live', $session->share_token) }}"><i class="bi bi-link-45deg" aria-hidden="true"></i>Copy link</button>
                <a class="btn-secondary min-h-9 px-3 text-xs" href="{{ route('open-plays.live', $session->share_token) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Open view</a>
                <span class="text-xs text-muted" role="status" aria-live="polite" data-copy-live-status></span>
            </div>
            <p class="mt-4 border-t border-line pt-3 text-[10px] leading-5 text-muted"><i class="bi bi-arrow-repeat mr-1 text-pine-mid" aria-hidden="true"></i>The spectator board refreshes automatically every 20 seconds.</p>
        </div>
    </dialog>
</body>
</html>
