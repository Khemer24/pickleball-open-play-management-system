<?php

namespace App\Policies;

use App\Models\OpenPlaySession;
use App\Models\User;

class OpenPlaySessionPolicy
{
    public function view(User $user, OpenPlaySession $session): bool
    {
        return $user->isAdmin()
            || $session->manager_id === $user->id
            || $session->organizer_id === $user->id
            || ($user->isManager() && $session->assignedManagers()->whereKey($user->id)->exists())
            || $session->players()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, OpenPlaySession $session): bool
    {
        return $user->isManager() && $session->manager_id === $user->id && ! $session->finalized_at;
    }

    public function organize(User $user, OpenPlaySession $session): bool
    {
        return $user->isOrganizer()
            && $session->organizer_id === $user->id
            && ! $session->finalized_at;
    }

    public function operate(User $user, OpenPlaySession $session): bool
    {
        if ($session->finalized_at) {
            return false;
        }

        if ($user->isOrganizer() && $session->organizer_id === $user->id) {
            return true;
        }

        if ($user->isManager()) {
            return ($session->organizer_id === null && $session->manager_id === $user->id)
                || $session->assignedManagers()->whereKey($user->id)->exists();
        }

        return false;
    }
}
