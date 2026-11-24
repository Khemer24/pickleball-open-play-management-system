<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionPlayer extends Model
{
    protected $fillable = ['open_play_session_id', 'user_id', 'guest_name', 'checked_in_at', 'fee_paid'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime', 'fee_paid' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchAppearances(): HasMany
    {
        return $this->hasMany(MatchParticipant::class);
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(MatchGame::class, 'match_participants', 'session_player_id', 'match_id')
            ->withPivot('team');
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Guest';
    }
}
