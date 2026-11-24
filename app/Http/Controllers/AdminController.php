<?php

namespace App\Http\Controllers;

use App\Models\SavedPlayerGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function managers(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $users = User::whereIn('role', ['manager', 'organizer'])->orderBy('name')->get();

        return view('admin.managers', [
            'managers' => $users,
            'pendingCount' => $users->filter(fn (User $user) => ! $this->isApproved($user))->count(),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(in_array($user->role, ['manager', 'organizer'], true), 404);
        $approvalColumn = $user->role === 'organizer' ? 'organizer_approved' : 'manager_approved';
        $user->update([$approvalColumn => true]);

        return back()->with('success', "{$user->name} has been approved as a {$user->role}.");
    }

    public function players(): View
    {
        return view('players.index', ['players' => User::where('role', 'player')->orderBy('name')->get()]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isManager(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $group = SavedPlayerGroup::create(['manager_id' => auth()->id(), 'name' => $data['name']]);
        $group->players()->sync(User::where('role', 'player')->whereIn('id', $data['player_ids'] ?? [])->pluck('id'));

        return back()->with('success', 'Player group saved for your next session.');
    }

    private function isApproved(User $user): bool
    {
        return $user->role === 'organizer' ? $user->organizer_approved : $user->manager_approved;
    }
}
