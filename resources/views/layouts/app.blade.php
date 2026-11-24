<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · RallyCourt</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen min-w-[320px] bg-paper font-sans text-ink antialiased">
    @php($current = request()->routeIs('dashboard') ? 'dashboard' : (request()->routeIs('events.*') ? 'events' : (request()->routeIs('open-plays.*') ? 'open-plays' : (request()->routeIs('players.*') ? 'players' : ''))))
    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-pine-deep px-4 py-6 text-white md:flex">
            <a class="flex items-center gap-2.5 px-2 text-lg font-extrabold tracking-tight text-white hover:text-white" href="{{ route('dashboard') }}">
                <span class="grid size-9 place-items-center rounded-xl border border-white/15 bg-white/10 text-lime">R</span>
                <span>RallyCourt</span>
            </a>
            <p class="mb-3 mt-10 px-3 text-[10px] font-bold uppercase tracking-[.15em] text-white/45">Workspace</p>
            <nav aria-label="Main navigation" class="space-y-1">
                <a class="side-link {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span> Dashboard</a>
                <a class="side-link {{ $current === 'open-plays' ? 'active' : '' }}" href="{{ route('open-plays.index') }}"><span class="nav-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span> Open Plays</a>
                <a class="side-link {{ $current === 'players' ? 'active' : '' }}" href="{{ route('players.index') }}"><span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span> Players</a>
                @if(auth()->user()->role === 'organizer')
                    <a class="side-link {{ $current === 'events' ? 'active' : '' }}" href="{{ route('events.index') }}"><span class="nav-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span> My Events</a>
                @endif
            </nav>
            @if(auth()->user()->isAdmin())
                <p class="mb-3 mt-8 px-3 text-[10px] font-bold uppercase tracking-[.15em] text-white/45">Administration</p>
                <a class="side-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.managers') }}"><span class="nav-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span> Role approvals</a>
            @endif
            <div class="mt-auto border-t border-white/10 pt-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2 transition-colors hover:bg-white/5">
                    @if(auth()->user()->avatar_path)
                        <img class="size-9 shrink-0 rounded-full object-cover" src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="">
                    @else
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-lime/20 text-xs font-bold text-lime">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</span>
                        <span class="block text-[10px] text-white/50">{{ ucfirst(auth()->user()->role) }}{{ (auth()->user()->role === 'manager' && !auth()->user()->manager_approved) || (auth()->user()->role === 'organizer' && !auth()->user()->organizer_approved) ? ' · awaiting approval' : '' }}</span>
                    </span>
                    <i class="bi bi-person-gear text-white/45" aria-hidden="true"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                    <button class="flex min-h-9 w-full items-center gap-3 rounded-lg px-3 text-xs font-semibold text-white/60 transition-colors hover:bg-white/10 hover:text-white" type="submit"><i class="bi bi-box-arrow-right w-5 text-center" aria-hidden="true"></i>Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <div class="flex min-h-14 items-center justify-between gap-3 bg-pine-deep px-4 py-2 text-white md:hidden">
                <a class="flex items-center gap-2 text-base font-extrabold text-white hover:text-white" href="{{ route('dashboard') }}"><span class="grid size-8 place-items-center rounded-lg border border-white/15 bg-white/10 text-sm text-lime">R</span>RallyCourt</a>
                <div class="flex items-center gap-2">
                    <span class="hidden max-w-36 truncate text-[11px] font-medium text-white/65 sm:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="grid size-9 place-items-center rounded-lg border border-white/15 text-white/80 transition-colors hover:bg-white/10" type="submit" aria-label="Sign out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button></form>
                </div>
            </div>
            <nav aria-label="Main navigation" class="flex gap-1 overflow-x-auto border-b border-white/10 bg-pine-deep px-2 py-2 md:hidden">
                <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Dashboard</a>
                <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ $current === 'open-plays' ? 'active' : '' }}" href="{{ route('open-plays.index') }}"><i class="bi bi-calendar2-week" aria-hidden="true"></i>Open Plays</a>
                <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ $current === 'players' ? 'active' : '' }}" href="{{ route('players.index') }}"><i class="bi bi-people" aria-hidden="true"></i>Players</a>
                @if(auth()->user()->role === 'organizer')
                    <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ $current === 'events' ? 'active' : '' }}" href="{{ route('events.index') }}"><i class="bi bi-calendar-event" aria-hidden="true"></i>My Events</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.managers') }}"><i class="bi bi-patch-check" aria-hidden="true"></i>Role approvals</a>
                @endif
                <a class="side-link my-0 min-h-9 shrink-0 gap-1.5 whitespace-nowrap px-2.5 text-[10px] {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear" aria-hidden="true"></i>Profile</a>
            </nav>
            <header class="sticky top-0 z-20 flex min-h-12 items-center justify-between gap-3 border-b border-line bg-surface/95 px-4 backdrop-blur sm:px-7 lg:px-9">
                <div class="min-w-0 truncate text-[11px] text-muted"><span class="hidden sm:inline">RallyCourt <span class="px-1 text-line">/</span></span><strong class="font-semibold text-pine">@yield('title', 'Dashboard')</strong></div>
                <span class="hidden shrink-0 rounded-full bg-pine-soft px-2.5 py-1 text-[9px] font-bold text-pine sm:inline-flex">{{ ucfirst(auth()->user()->role) }} workspace</span>
            </header>
            <main class="min-w-0 px-4 py-5 sm:px-7 sm:py-7 xl:px-9">
                @if(session('success'))<div class="status-message status-success mb-4" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())
                    <div class="status-message status-error mb-4" role="alert"><strong>Please check the form:</strong><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
