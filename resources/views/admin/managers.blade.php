@extends('layouts.app')
@section('title', 'Role approvals')
@section('content')
<header class="mb-6"><div class="eyebrow">Administration</div><h1>Role approvals</h1><p class="subheading">Approve organizers and managers to create or run open plays.</p></header>
<section class="ui-card panel-card">
    <div class="card-heading"><div><h2>Organizer and Manager applications</h2><p>{{ $pendingCount }} waiting for review</p></div></div>
    <div class="overflow-x-auto"><table class="w-full min-w-[600px] text-left text-xs"><thead class="border-b border-line text-[10px] uppercase tracking-wide text-muted"><tr><th class="py-2 pr-3">Name</th><th class="py-2 pr-3">Email</th><th class="py-2 pr-3">Requested role</th><th class="py-2 pr-3">Status</th><th class="py-2 text-right"></th></tr></thead><tbody>
    @foreach($managers as $manager)@php($approved = $manager->role === 'organizer' ? $manager->organizer_approved : $manager->manager_approved)<tr class="border-b border-line last:border-0"><td class="py-3 pr-3 font-semibold text-pine">{{ $manager->name }}</td><td class="py-3 pr-3 text-muted">{{ $manager->email }}</td><td class="py-3 pr-3 capitalize">{{ $manager->role }}</td><td class="py-3 pr-3">@if($approved)<span class="badge-success">Approved</span>@else<span class="badge-warning">Pending</span>@endif</td><td class="py-3 text-right">@unless($approved)<form method="POST" action="{{ route('admin.managers.approve',$manager) }}">@csrf<button class="btn-primary min-h-8 px-3 py-1 text-xs">Approve {{ $manager->role }}</button></form>@endunless</td></tr>@endforeach
    @if($managers->isEmpty())<tr><td colspan="4" class="py-6 text-center text-muted">No manager accounts yet.</td></tr>@endif
    </tbody></table></div>
</section>
@endsection
