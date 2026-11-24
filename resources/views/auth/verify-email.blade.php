<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102d23">
    <title>Verify email · RallyCourt</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen min-w-[320px] bg-paper font-sans text-ink antialiased">
    <main class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-8 sm:px-7">
        <a class="brand-link mb-5 p-0 text-pine hover:text-pine" href="{{ route('dashboard') }}"><span class="brand-mark border-court-line bg-pine-soft">R</span> RallyCourt</a>
        <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
            <div class="bg-pine-deep px-5 py-5 text-white sm:px-7">
                <span class="inline-grid size-10 place-items-center rounded-xl bg-lime text-lg text-pine-deep"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
                <p class="mt-4 text-[10px] font-bold uppercase tracking-[.15em] text-lime">One quick step</p>
                <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Check your inbox</h1>
            </div>
            <div class="p-5 sm:p-7">
                <p class="mb-5 text-sm leading-6 text-muted">We sent a verification link to <strong class="break-all text-pine">{{ auth()->user()->email }}</strong>. Open it to activate your account.</p>
                @if(session('status'))<div class="status-message status-success mb-4" role="status">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="status-message status-error mb-4" role="alert">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn-primary w-full sm:w-auto">Send a fresh link <i class="bi bi-arrow-right" aria-hidden="true"></i></button></form>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="btn-outline">Sign out</button></form>
            </div>
        </section>
        <p class="mt-4 text-center text-[10px] text-muted">RallyCourt · Better open play, one court at a time.</p>
    </main>
</body>
</html>
