<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#102d23"><title>RallyCourt · Open play, better organized</title>@vite(['resources/js/app.js'])</head>
<body class="bg-paper">
<main class="mx-auto max-w-7xl px-4 py-5 sm:px-7 lg:px-10">
    <header class="flex items-center justify-between gap-4">
        <a class="brand-link p-0 text-pine hover:text-pine" href="{{ route('login') }}"><span class="brand-mark border-court-line bg-pine-soft">R</span> RallyCourt</a>
        <nav aria-label="Account" class="flex items-center gap-2"><a class="btn-secondary" href="{{ route('login') }}">Sign in</a><a class="btn-primary" href="{{ route('register') }}">Join the community <i class="bi bi-arrow-right" aria-hidden="true"></i></a></nav>
    </header>
    <section class="relative mt-6 grid min-h-[440px] items-center gap-8 overflow-hidden rounded-3xl bg-pine-deep px-6 py-12 text-white sm:px-10 lg:grid-cols-[1.1fr_.9fr] lg:px-14">
        <span class="pointer-events-none absolute -right-20 top-0 h-[150%] w-px rotate-[30deg] bg-white/10" aria-hidden="true"></span>
        <div class="relative z-10"><div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[.14em] text-[#c9ee64]"><i class="bi bi-circle-fill text-[7px]" aria-hidden="true"></i> RallyCourt open play</div><h1 class="max-w-2xl text-4xl leading-[1.05] text-white sm:text-5xl lg:text-6xl">Good games start with a great <span class="text-lime">rally.</span></h1><p class="mt-5 max-w-lg text-sm text-white/75 sm:text-base">Bring your pickleball community together. Keep the queue moving, courts in play, and every result in one place.</p><div class="mt-7 flex flex-wrap gap-3"><a class="btn-primary py-3" href="{{ route('register') }}">Get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a><a class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 hover:text-white" href="{{ route('login') }}">Explore your dashboard</a></div></div>
        <div class="relative mx-auto w-full max-w-md rounded-2xl border border-white/15 bg-white/[.07] p-4 shadow-2xl">
            <div class="mb-4 flex items-center justify-between"><div><div class="text-[10px] font-semibold uppercase tracking-widest text-white/55">Saturday open play</div><div class="mt-1 text-sm font-bold text-white">Riverside Pickleball Club</div></div><span class="live-pill">● LIVE NOW</span></div>
            <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-xl border border-white/15 bg-[#1b4835] p-3"><div class="mb-3 flex justify-between text-[10px] font-bold text-white/65"><span>COURT 1</span><span class="text-lime">IN PLAY</span></div><div class="rounded-lg bg-white/10 px-3 py-2 text-xs text-white">Avery & Jordan</div><div class="my-1 text-center text-[10px] text-white/50">vs</div><div class="rounded-lg bg-white/10 px-3 py-2 text-xs text-white">Sam & Casey</div></div><div class="rounded-xl border border-white/15 bg-[#1b4835] p-3"><div class="mb-3 flex justify-between text-[10px] font-bold text-white/65"><span>COURT 2</span><span class="text-lime">IN PLAY</span></div><div class="rounded-lg bg-white/10 px-3 py-2 text-xs text-white">Taylor & Morgan</div><div class="my-1 text-center text-[10px] text-white/50">vs</div><div class="rounded-lg bg-white/10 px-3 py-2 text-xs text-white">Drew & Jamie</div></div></div>
            <div class="mt-3 flex items-center gap-3 rounded-xl bg-white/[.06] px-3 py-2"><span class="grid size-7 place-items-center rounded-full bg-lime text-xs font-bold text-pine">4</span><span class="text-xs font-semibold text-white/80">players ready in the queue</span><span class="ml-auto text-[10px] text-white/50">UP NEXT</span></div>
        </div>
    </section>
    <section class="grid gap-3 py-8 sm:grid-cols-3">
        <article class="ui-card p-5"><span class="grid size-10 place-items-center rounded-xl bg-pine-soft text-lg text-pine"><i class="bi bi-people" aria-hidden="true"></i></span><h2 class="mt-4 text-base">Bring players together</h2><p class="mt-2 text-sm text-muted">Add your regular crew, invite registered players, or check in guests by name.</p></article>
        <article class="ui-card p-5"><span class="grid size-10 place-items-center rounded-xl bg-pine-soft text-lg text-pine"><i class="bi bi-grid-3x3" aria-hidden="true"></i></span><h2 class="mt-4 text-base">Keep courts moving</h2><p class="mt-2 text-sm text-muted">Fair matchups and a live waiting queue make open play simple to run.</p></article>
        <article class="ui-card p-5"><span class="grid size-10 place-items-center rounded-xl bg-pine-soft text-lg text-pine"><i class="bi bi-bar-chart" aria-hidden="true"></i></span><h2 class="mt-4 text-base">Celebrate every rally</h2><p class="mt-2 text-sm text-muted">Record results and follow your wins, partners, and toughest matchups.</p></article>
    </section>
    <footer class="border-t border-line py-5 text-center text-xs text-muted">RallyCourt · Better open play, one court at a time.</footer>
</main>
</body>
</html>
