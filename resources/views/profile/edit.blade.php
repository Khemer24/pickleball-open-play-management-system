@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<header class="mb-6"><div class="eyebrow">Your account</div><h1>Profile settings</h1><p class="subheading">Keep your player profile up to date.</p></header>
<div class="max-w-3xl">
<section class="ui-card panel-card">
    <div class="card-heading"><div><h2>Personal details</h2><p>Your photo and information shown to your community</p></div></div>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="mb-5 flex flex-wrap items-center gap-4">
            @if(auth()->user()->avatar_path)<img class="avatar size-16" src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="Current profile photo">@else<span class="avatar size-16 text-xl">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>@endif
            <div class="min-w-[220px] flex-1"><label class="field-label" for="avatar">Profile photo</label><input id="avatar" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-pine-soft file:px-3 file:py-1 file:text-xs file:font-semibold file:text-pine" type="file" name="avatar" accept="image/*"><small class="mt-1 block text-xs text-muted">JPG, PNG, GIF or WebP up to 3 MB.</small></div>
        </div>
        <div class="mb-4"><label class="field-label" for="name">Name</label><input id="name" class="field" name="name" value="{{ old('name',auth()->user()->name) }}" required></div>
        <div class="mb-5"><label class="field-label" for="email">Email</label><input id="email" class="field" type="email" name="email" value="{{ old('email',auth()->user()->email) }}" required>@if(!auth()->user()->hasVerifiedEmail())<small class="mt-1 block text-xs text-[#8b5e18]">Please verify your email address.</small>@endif</div>
        <button class="btn-primary px-5">Save changes</button>
    </form>
</section>
</div>
@endsection
