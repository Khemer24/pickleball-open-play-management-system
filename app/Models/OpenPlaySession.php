<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenPlaySession extends Model
{
    protected $fillable = [
        'manager_id', 'organizer_id', 'name', 'venue', 'play_date', 'starts_at', 'skill_level', 'format',
        'player_limit', 'rules', 'scheduling_paused', 'share_token', 'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'play_date' => 'date',
            'starts_at' => 'datetime',
            'scheduling_paused' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function assignedManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'open_play_session_managers', 'open_play_session_id', 'manager_id')
            ->withTimestamps();
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class)->orderBy('sort_order');
    }

    public function players(): HasMany
    {
        return $this->hasMany(SessionPlayer::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'open_play_session_id');
    }
}
