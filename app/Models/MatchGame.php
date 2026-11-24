<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatchGame extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'open_play_session_id', 'court_id', 'status', 'team_one_score', 'team_two_score', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(SessionPlayer::class, 'match_participants', 'match_id', 'session_player_id')
            ->withPivot('team');
    }

    public function participantRows()
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }
}
