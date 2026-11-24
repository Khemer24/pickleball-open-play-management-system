<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · RallyCourt</title>@vite(['resources/js/app.js'])</head>
<body>
<div class="auth-wrap">
    <section class="auth-visual relative isolate overflow-hidden">
        <svg class="pointer-events-none absolute -bottom-5 -right-20 h-56 w-[390px] rotate-[-12deg] opacity-40" viewBox="0 0 360 200" fill="none" aria-hidden="true"><path d="M45 34 306 23l24 142-264 12L45 34Z" stroke="#C9EE64" stroke-width="8" stroke-linejoin="round"/><path d="m66 49 222-9 17 111-224 10L66 49Zm113-5 8 111M66 103l236-10M113 47l10 117m128-122 10 117" stroke="#C9EE64" stroke-width="2"/></svg>
        <a class="brand-link relative z-10 p-0" href="{{ route('login') }}"><span class="brand-mark">R</span> RallyCourt</a><div class="relative z-10"><div class="eyebrow text-white/60">A better kind of open play</div><h1 class="my-4 text-4xl leading-tight text-white lg:text-5xl">Show up.<br>Find your game.</h1><p class="max-w-sm text-sm text-white/75">One place to bring your pickleball community together, court by court.</p></div><div class="relative z-10 text-xs text-white/60">Make every open play a good one.</div>
    </section>
    <main class="auth-form">
        <a class="brand-link mb-8 p-0 text-pine hover:text-pine lg:hidden" href="{{ route('login') }}"><span class="brand-mark border-court-line bg-pine-soft">R</span> RallyCourt</a>
        <div class="eyebrow mb-2">Welcome back</div><h2 class="mb-1 text-2xl">Sign in to your account</h2><p class="mb-5 text-sm text-muted">Your next game is waiting.</p>
        @if($errors->any())<div class="status-message status-error mb-4" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.store') }}">@csrf
            <div class="mb-4"><label class="field-label" for="email">Email address</label><input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
            <div class="mb-4"><label class="field-label" for="password">Password</label><input id="password" class="field" type="password" name="password" required autocomplete="current-password"></div>
            <label class="mb-5 flex cursor-pointer items-center gap-2 text-xs text-muted"><input class="accent-pine-mid" type="checkbox" name="remember" id="remember"> Keep me signed in</label>
            <button class="btn-primary w-full py-3">Sign in</button>
        </form>
        <p class="mt-5 text-center text-xs text-muted">New to RallyCourt? <a class="font-bold text-pine underline decoration-court-line underline-offset-2" href="{{ route('register') }}">Create an account</a></p>
        <p class="mt-2 text-center text-xs text-muted">Want to help run an open play? <a class="font-semibold text-pine underline decoration-court-line underline-offset-2" href="{{ route('register', ['role' => 'manager']) }}">Apply as a manager</a> or <a class="font-semibold text-pine underline decoration-court-line underline-offset-2" href="{{ route('register', ['role' => 'organizer']) }}">organizer</a>.</p>
    </main>
</div>
</body></html>
