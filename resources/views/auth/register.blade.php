<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Create account · RallyCourt</title>@vite(['resources/js/app.js'])</head>
<body>
<div class="auth-wrap">
    <section class="auth-visual relative isolate overflow-hidden">
        <svg class="pointer-events-none absolute -bottom-5 -right-20 h-56 w-[390px] rotate-[-12deg] opacity-40" viewBox="0 0 360 200" fill="none" aria-hidden="true"><path d="M45 34 306 23l24 142-264 12L45 34Z" stroke="#C9EE64" stroke-width="8" stroke-linejoin="round"/><path d="m66 49 222-9 17 111-224 10L66 49Zm113-5 8 111M66 103l236-10M113 47l10 117m128-122 10 117" stroke="#C9EE64" stroke-width="2"/></svg>
        <a class="brand-link relative z-10 p-0" href="{{ route('login') }}"><span class="brand-mark">R</span> RallyCourt</a><div class="relative z-10"><div class="eyebrow text-white/60">{{ $organizer ? 'For event organizers' : ($manager ? 'For court managers' : 'Your pickleball community') }}</div><h1 class="my-4 text-4xl leading-tight text-white lg:text-5xl">{{ $organizer ? 'Bring your community to the court.' : ($manager ? 'Keep every court moving.' : 'Your next game starts here.') }}</h1><p class="max-w-sm text-sm text-white/75">{{ $organizer ? 'Create open plays, assign approved managers, and keep every result organized.' : ($manager ? 'Apply to help run open plays. An administrator will approve your manager account.' : 'Join sessions, track your matches, and find your people.') }}</p></div><div class="relative z-10 text-xs text-white/60">Play more. Keep it friendly.</div>
    </section>
    <main class="auth-form">
        <a class="brand-link mb-8 p-0 text-pine hover:text-pine lg:hidden" href="{{ route('login') }}"><span class="brand-mark border-court-line bg-pine-soft">R</span> RallyCourt</a>
        <div class="eyebrow mb-2">Get started</div><h2 class="mb-1 text-2xl">{{ $organizer ? 'Apply to organize' : ($manager ? 'Apply to manage' : 'Create your account') }}</h2><p class="mb-5 text-sm text-muted">We’ll send a link to verify your email.</p>
        @if($errors->any())<div class="status-message status-error mb-4" role="alert"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('register.store') }}">@csrf
            <div class="mb-3"><label class="field-label" for="name">Full name</label><input id="name" class="field" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
            <div class="mb-3"><label class="field-label" for="email">Email address</label><input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
            <div class="mb-3"><label class="field-label" for="password">Password</label><input id="password" class="field" type="password" name="password" required autocomplete="new-password"></div>
            <div class="mb-4"><label class="field-label" for="password-confirmation">Confirm password</label><input id="password-confirmation" class="field" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <input type="hidden" name="role" value="{{ $organizer ? 'organizer' : ($manager ? 'manager' : 'player') }}">
            <button class="btn-primary w-full py-3">{{ $organizer ? 'Apply for organizer access' : ($manager ? 'Apply for manager access' : 'Create account') }}</button>
        </form>
        <p class="mt-5 text-center text-xs text-muted">Already registered? <a class="font-bold text-pine underline decoration-court-line underline-offset-2" href="{{ route('login') }}">Sign in</a></p>
    </main>
</div>
</body></html>
